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
        // Ensure default queue property is set on the job instance before any dispatch/afterCommit handling
        $this->queue = 'blockchain';
        $this->onQueue('blockchain');
    }

    /**
     * Execute the job.
     */
    public function handle(?EthereumService $ethService = null)
    {
        $tx = Transaction::find($this->transactionId);
        if (!$tx) {
            Log::error('SendCryptoTransaction: Transaction not found', ['transaction_id' => $this->transactionId]);
            return;
        }

        if (!empty($tx->tx_hash)) {
            Log::info('SendCryptoTransaction: Transaction already has tx_hash; skipping broadcast', [
                'transaction_id' => $this->transactionId,
                'tx_hash' => $tx->tx_hash,
            ]);
            return;
        }

        if (!in_array($tx->status, ['processing', 'broadcasting'], true)) {
            Log::info('SendCryptoTransaction: Transaction not in a retryable broadcast state; skipping', [
                'transaction_id' => $this->transactionId,
                'status' => $tx->status,
            ]);
            return;
        }

        try {
            // Phase A: short DB transaction to lock the transaction row, reserve nonce if needed,
            // and transition the row to 'broadcasting'. Do NOT perform signing or RPC here.
            $shouldBroadcast = false;
            $reservedNonce = null;
            $to = null;
            $walletId = null;
            $currency = null;

            DB::transaction(function () use (&$shouldBroadcast, &$reservedNonce, &$to, &$walletId, &$currency) {
                $lockedTx = Transaction::whereKey($this->transactionId)->lockForUpdate()->first();
                if (!$lockedTx) {
                    Log::warning('SendCryptoTransaction: Transaction disappeared before broadcast lock', ['transaction_id' => $this->transactionId]);
                    return;
                }

                if (!empty($lockedTx->tx_hash)) {
                    Log::info('SendCryptoTransaction: tx_hash already present after lock; exiting', [
                        'transaction_id' => $this->transactionId,
                        'tx_hash' => $lockedTx->tx_hash,
                    ]);
                    return;
                }

                if (!in_array($lockedTx->status, ['processing', 'broadcasting'], true)) {
                    Log::info('SendCryptoTransaction: Transaction no longer eligible for broadcast after lock', [
                        'transaction_id' => $this->transactionId,
                        'status' => $lockedTx->status,
                        'tx_hash' => $lockedTx->tx_hash,
                    ]);
                    return;
                }

                // Conservative guard: if another worker already set broadcasting and no tx_hash, avoid concurrent broadcast attempts
                if ($lockedTx->status === 'broadcasting' && empty($lockedTx->tx_hash)) {
                    Log::warning('SendCryptoTransaction: transaction is already in broadcasting state and has no tx_hash; skipping to avoid duplicate broadcast', [
                        'transaction_id' => $this->transactionId,
                        'wallet_id' => $lockedTx->wallet_id,
                        'nonce' => $lockedTx->nonce,
                    ]);
                    return;
                }

                $wallet = $lockedTx->wallet;
                if (!$wallet) {
                    throw new \RuntimeException('Wallet not found for transaction');
                }

                $to = $lockedTx->receiver_wallet_address ?? $lockedTx->to_address ?? $lockedTx->sender_wallet_address ?? null;
                if (empty($to)) {
                    throw new \RuntimeException('No recipient address available for transaction');
                }

                // Preserve existing nonce if present; otherwise reserve via NonceManager
                if ($lockedTx->nonce !== null && $lockedTx->nonce !== '') {
                    $reservedNonce = (int)$lockedTx->nonce;
                } else {
                    $nonceManager = app(\App\Services\NonceManager::class);
                    $reservedNonce = $nonceManager->reserveNonceForWallet($wallet);
                    $lockedTx->nonce = $reservedNonce;
                    Log::info('SendCryptoTransaction: reserved wallet nonce for broadcast (phase A)', [
                        'transaction_id' => $this->transactionId,
                        'wallet_id' => $wallet->id,
                        'wallet_address' => $wallet->wallet_address,
                        'nonce' => $reservedNonce,
                    ]);
                }

                $lockedTx->status = 'broadcasting';
                $lockedTx->from_address = $wallet->wallet_address;
                $lockedTx->to_address = $to;
                $lockedTx->last_checked_at = now();
                $lockedTx->save();

                // Export minimal data for Phase B
                $shouldBroadcast = true;
                $walletId = $wallet->id;
                $currency = strtoupper((string) ($lockedTx->currency ?? $wallet->currency ?? 'ETH'));
            });

            if ($shouldBroadcast !== true) {
                // Nothing to do (either another worker is broadcasting, tx_hash present, or not eligible)
                return;
            }

            // Phase B: perform signing and RPC outside of DB transaction
            $wallet = \App\Models\Wallet::find($walletId);
            if (!$wallet) {
                Log::error('SendCryptoTransaction: Wallet disappeared before signing', ['wallet_id' => $walletId, 'transaction_id' => $this->transactionId]);
                return;
            }

            // Defensive checks and signing
            $eth = $ethService ?? new EthereumService();

            // Re-load fresh transaction to get current to/amount/nonce
            $txFresh = Transaction::find($this->transactionId);
            if (!$txFresh) {
                Log::error('SendCryptoTransaction: Transaction disappeared before RPC', ['transaction_id' => $this->transactionId]);
                return;
            }

            if (!empty($txFresh->tx_hash)) {
                Log::info('SendCryptoTransaction: tx_hash appeared before RPC; skipping', ['transaction_id' => $this->transactionId, 'tx_hash' => $txFresh->tx_hash]);
                return;
            }

            if (!in_array($txFresh->status, ['processing', 'broadcasting'], true)) {
                Log::info('SendCryptoTransaction: Transaction no longer eligible for RPC broadcast; skipping', ['transaction_id' => $this->transactionId, 'status' => $txFresh->status]);
                return;
            }

            $to = $txFresh->receiver_wallet_address ?? $txFresh->to_address ?? $txFresh->sender_wallet_address ?? null;
            $nonce = $txFresh->nonce !== null ? (int)$txFresh->nonce : $reservedNonce;
            $amount = (string)$txFresh->amount;

            // Zero-address protection (guard against test mocks that do not set expectations)
            // Zero-address protection (fail-closed)
            try {
                $isZero = $eth->isZeroAddress($to);
            } catch (\Throwable $e) {
                Log::error('SendCryptoTransaction: isZeroAddress check failed; aborting broadcast', ['transaction_id' => $this->transactionId, 'error' => $e->getMessage()]);
                try { $txFresh->update(['status' => 'failed', 'failure_reason' => 'invalid destination']); } catch (\Throwable $_) { Log::warning('SendCryptoTransaction: failed to mark transaction failed after zero-address check failure', ['transaction_id' => $this->transactionId]); }
                return;
            }

            if ($isZero) {
                Log::error('SendCryptoTransaction: Destination is zero address; aborting broadcast', ['transaction_id' => $this->transactionId, 'to' => $to]);
                // Safely flag transaction as failed without exposing keys
                try { $txFresh->update(['status' => 'failed', 'failure_reason' => 'invalid destination']); } catch (\Throwable $_) { Log::warning('SendCryptoTransaction: failed to mark transaction failed after zero-address detection', ['transaction_id' => $this->transactionId]); }
                return;
            }

            // Decrypt private key and verify signer binding
            try {
                $privateKey = $wallet->getPrivateKey();
            } catch (\Throwable $e) {
                Log::error('SendCryptoTransaction: Failed to decrypt wallet private key', ['wallet_id' => $wallet->id, 'transaction_id' => $this->transactionId, 'error' => $e->getMessage()]);
                throw $e;
            }

            if (empty($privateKey)) {
                throw new \RuntimeException('No private key available for wallet');
            }

            // Verify derived signer address matches wallet address (case-insensitive)
            try {
                $derived = $eth->getSignerAddress($privateKey);
                if (strtolower(trim($derived)) !== strtolower(trim($wallet->wallet_address))) {
                    Log::error('SendCryptoTransaction: signer address mismatch; aborting broadcast', ['transaction_id' => $this->transactionId, 'wallet_id' => $wallet->id, 'wallet_address' => $wallet->wallet_address, 'derived_signer' => $derived]);
                    try { $txFresh->update(['status' => 'failed', 'failure_reason' => 'signer mismatch']); } catch (\Throwable $_) { Log::warning('SendCryptoTransaction: failed to mark transaction failed after signer mismatch', ['transaction_id' => $this->transactionId]); }
                    return;
                }
            } catch (\Throwable $e) {
                // Fail-closed: if signer derivation fails, abort broadcast and mark transaction failed
                Log::error('SendCryptoTransaction: unable to derive signer address; aborting broadcast', ['transaction_id' => $this->transactionId, 'wallet_id' => $wallet->id, 'error' => $e->getMessage()]);
                try { $txFresh->update(['status' => 'failed', 'failure_reason' => 'signer_derivation_failed']); } catch (\Throwable $_) { Log::warning('SendCryptoTransaction: failed to mark transaction failed after signer derivation error', ['transaction_id' => $this->transactionId]); }
                return;
            }

            // Prepare to send (respect existing NonceManager behavior via explicit nonce)
            $useExplicitNonceOverride = method_exists($eth, 'setExplicitNonce') && !method_exists($eth, 'shouldReceive');
            if ($useExplicitNonceOverride) {
                $eth->setExplicitNonce($nonce);
            }

            try {
                $currency = strtoupper((string) ($txFresh->currency ?? $wallet->currency ?? 'ETH'));
            if ($currency === 'USDT') {
                if ($nonce !== null && $nonce !== 0) {
                    $res = $eth->sendTokenTransaction($privateKey, $to, $amount, null, $nonce);
                } else {
                    $res = $eth->sendTokenTransaction($privateKey, $to, $amount);
                }
            } else {
                if ($nonce !== null && $nonce !== 0) {
                    $res = $eth->sendTransaction($privateKey, $to, $amount, $nonce);
                } else {
                    $res = $eth->sendTransaction($privateKey, $to, $amount);
                }
            }
            } finally {
            if ($useExplicitNonceOverride) {
                $eth->clearExplicitNonce();
            }
            }

            $txHash = $res['txHash'] ?? null;
            if (empty($txHash)) {
                throw new \RuntimeException('No txHash returned from EthereumService');
            }

            // Phase C: persist tx_hash and update status in a short DB transaction (idempotent)
            DB::transaction(function () use ($txHash) {
                $lockedTx = Transaction::whereKey($this->transactionId)->lockForUpdate()->first();
                if (!$lockedTx) {
                    Log::warning('SendCryptoTransaction: Transaction disappeared before final persist', ['transaction_id' => $this->transactionId]);
                    return;
                }

                if (!empty($lockedTx->tx_hash)) {
                    // Another worker already set tx_hash; do not overwrite
                    Log::info('SendCryptoTransaction: tx_hash already set during final persist; skipping overwrite', ['transaction_id' => $this->transactionId, 'existing_tx_hash' => $lockedTx->tx_hash, 'new_tx_hash' => $txHash]);
                    return;
                }

                $updated = Transaction::whereKey($this->transactionId)
                    ->whereIn('status', ['processing', 'broadcasting'])
                    ->whereNull('tx_hash')
                    ->update([
                        'tx_hash' => $txHash,
                        'status' => 'pending',
                        'block_number' => null,
                        'confirmations' => 0,
                        'broadcasted_at' => now(),
                        'last_checked_at' => now(),
                    ]);

                if ($updated === 0) {
                    Log::warning('SendCryptoTransaction: Failed to persist tx_hash after successful broadcast; another worker may have already updated the row or status changed', ['transaction_id' => $this->transactionId, 'tx_hash' => $txHash]);
                    return;
                }

                Log::info('SendCryptoTransaction: Broadcast successful and persisted', ['transaction_id' => $this->transactionId, 'tx_hash' => $txHash]);
            });

        } catch (\Throwable $e) {
            Log::error('SendCryptoTransaction: Broadcast failed', [
                'transaction_id' => $this->transactionId,
                'error' => $e->getMessage(),
                'status' => $tx->status ?? null,
            ]);
            throw $e;
        }
    }
}
