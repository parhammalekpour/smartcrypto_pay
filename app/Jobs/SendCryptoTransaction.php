<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Services\EthereumService;
use App\Services\BalanceSyncService;

class SendCryptoTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $transactionId;

    // Limits and backoff per requirements
    public int $tries = 3;
    // backoff in seconds between retries (first retry after 10s, next after 30s)
    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     * Only pass transaction id to the queue payload — do NOT include private keys.
     */
    public function __construct(int $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Re-fetch transaction fresh
        $tx = Transaction::find($this->transactionId);
        if (!$tx) {
            Log::error('SendCryptoTransaction: Transaction not found', ['transaction_id' => $this->transactionId]);
            return;
        }

        // Only process transactions that are in 'processing' state
        if ($tx->status !== 'processing') {
            Log::info('SendCryptoTransaction: Transaction not in processing state; skipping', [
                'transaction_id' => $this->transactionId,
                'status' => $tx->status,
            ]);
            return;
        }

        // If tx_hash already present then assume already broadcasted
        if (!empty($tx->tx_hash)) {
            Log::info('SendCryptoTransaction: Transaction already has tx_hash; skipping broadcast', [
                'transaction_id' => $this->transactionId,
                'tx_hash' => $tx->tx_hash,
            ]);
            // Ensure status is pending at least
            if ($tx->status !== 'pending') {
                try {
                    $tx->status = 'pending';
                    $tx->save();
                } catch (\Throwable $e) {
                    Log::error('SendCryptoTransaction: Failed to mark existing tx as pending', ['transaction_id' => $this->transactionId, 'error' => $e->getMessage()]);
                }
            }

            // Ensure wallet balance is synchronized so UI reflects spent amount
            try {
                $walletToSync = $tx->wallet;
                if ($walletToSync) {
                    $balanceService = new BalanceSyncService();
                    $balanceService->syncWallet($walletToSync);
                }
            } catch (\Throwable $_) {
                Log::warning('SendCryptoTransaction: balance sync failed for already-broadcast tx ' . $this->transactionId);
            }

            return;
        }

        $wallet = $tx->wallet;
        if (!$wallet) {
            Log::error('SendCryptoTransaction: Wallet not found for transaction', ['transaction_id' => $this->transactionId]);
            $tx->status = 'failed';
            $tx->save();
            return;
        }

        // Decrypt private key securely in memory
        try {
            $privateKey = $wallet->getPrivateKey();
        } catch (\Throwable $e) {
            Log::error('SendCryptoTransaction: Failed to decrypt wallet private key', ['wallet_id' => $wallet->id, 'transaction_id' => $this->transactionId, 'error' => $e->getMessage()]);
            $tx->status = 'failed';
            $tx->save();
            return;
        }

        if (empty($privateKey)) {
            Log::error('SendCryptoTransaction: No private key available for wallet', ['wallet_id' => $wallet->id, 'transaction_id' => $this->transactionId]);
            $tx->status = 'failed';
            $tx->save();
            return;
        }

        $eth = new EthereumService();

        // Re-check that transaction still hasn't been broadcasted (race-safety)
        $freshTx = Transaction::find($this->transactionId);
        if (!empty($freshTx->tx_hash)) {
            Log::info('SendCryptoTransaction: tx_hash appeared before broadcast; aborting', ['transaction_id' => $this->transactionId, 'tx_hash' => $freshTx->tx_hash]);
            return;
        }

        // Prepare broadcast: call sendTransaction which signs and sends
        try {
            $to = $tx->receiver_wallet_address ?? $tx->receiver_address ?? null;
            if (empty($to)) {
                $to = $tx->receiver_wallet_address ?? $tx->receiver_wallet_address;
            }

            $amount = (string)$tx->amount;

            // Estimate + send are encapsulated in EthereumService; keep estimate in job as required
            // Use sendTransaction which expects private key, to, amount (ETH)
            $res = $eth->sendTransaction($privateKey, $to, $amount);

            $txHash = $res['txHash'] ?? null;

            if (empty($txHash)) {
                throw new \RuntimeException('No txHash returned from EthereumService');
            }

            // Persist tx_hash and update status -> pending, but use conditional update to avoid race dupes
            $updated = Transaction::where('id', $this->transactionId)
                ->whereNull('tx_hash')
                ->update([
                    'tx_hash' => $txHash,
                    'status' => 'pending',
                    'block_number' => null,
                    'confirmations' => 0,
                ]);

            if ($updated === 0) {
                // Another worker/process saved tx_hash concurrently
                Log::warning('SendCryptoTransaction: Failed to save tx_hash because record changed concurrently', ['transaction_id' => $this->transactionId, 'tx_hash' => $txHash]);

                // Attempt to ensure balance is in sync in case another process completed the update
                try {
                    $maybeWallet = $tx->wallet;
                    if ($maybeWallet) {
                        $balanceService = new BalanceSyncService();
                        $balanceService->syncWallet($maybeWallet);
                    }
                } catch (\Throwable $_) {
                    Log::warning('SendCryptoTransaction: balance sync failed after concurrent save for tx ' . $this->transactionId);
                }
            } else {
                Log::info('SendCryptoTransaction: Broadcast successful', ['transaction_id' => $this->transactionId, 'tx_hash' => $txHash]);

                // After successful broadcast and DB update, synchronize wallet balance so the spent amount is applied exactly once
                try {
                    $walletToSync = $tx->wallet;
                    if ($walletToSync) {
                        $balanceService = new BalanceSyncService();
                        $balanceService->syncWallet($walletToSync);
                    }
                } catch (\Throwable $e) {
                    Log::error('SendCryptoTransaction: Failed to sync wallet balance after broadcast', ['transaction_id' => $this->transactionId, 'error' => $e->getMessage()]);
                }
            }

            return;
        } catch (\Throwable $e) {
            // Before marking as failed, attempt to detect if transaction may have been broadcast by inspecting DB again
            $maybeTx = Transaction::find($this->transactionId);
            if (!empty($maybeTx->tx_hash)) {
                Log::info('SendCryptoTransaction: Detected tx_hash after exception; treating as success', ['transaction_id' => $this->transactionId, 'tx_hash' => $maybeTx->tx_hash]);

                if (!in_array($maybeTx->status, ['pending', 'confirmed', 'completed'], true)) {
                    try {
                        $maybeTx->status = 'pending';
                        $maybeTx->save();
                    } catch (\Throwable $_) {
                        Log::warning('SendCryptoTransaction: Failed to mark tx as pending after exception detection', ['transaction_id' => $this->transactionId, 'tx_hash' => $maybeTx->tx_hash]);
                    }
                }

                // Ensure wallet balance is synced
                try {
                    $maybeWallet = $maybeTx->wallet;
                    if ($maybeWallet) {
                        $balanceService = new BalanceSyncService();
                        $balanceService->syncWallet($maybeWallet);
                    }
                } catch (\Throwable $_) {
                    Log::warning('SendCryptoTransaction: balance sync failed after detecting tx_hash for tx ' . $this->transactionId);
                }

                return;
            }

            Log::error('SendCryptoTransaction: Broadcast failed', ['transaction_id' => $this->transactionId, 'error' => $e->getMessage()]);

            // Mark as failed — allow retries according to $tries; when retries exhausted Laravel will move job to failed_jobs and the admin can inspect
            try {
                $tx->status = 'failed';
                $tx->save();
            } catch (\Throwable $_) {
                Log::error('SendCryptoTransaction: Failed to mark transaction as failed in DB', ['transaction_id' => $this->transactionId]);
            }

            // Rethrow to allow Laravel to handle retries
            throw $e;
        }
    }
}
