<?php

namespace App\Jobs;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\BalanceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\EthereumService;
use Illuminate\Support\Facades\DB;

class UpdateDepositConfirmationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct()
    {
    }

    public function handle(EthereumService $ethService, BalanceSyncService $balanceService)
    {
        Log::info('Confirmation updater job started');

        try {
            $currentBlock = null;
            try {
                $currentBlock = $ethService->getCurrentBlockNumber();
            } catch (\Throwable $e) {
                Log::error('Failed to get current block number for confirmations update: ' . $e->getMessage());
            }

            // Determine confirmation threshold (use centralized config to avoid duplication)
            $threshold = (int) config('ethereum.confirmation_threshold');

            // Find pending deposits which have a block_number
            $pending = Deposit::where('status', 'pending')
                ->whereNotNull('block_number')
                ->get();

            Log::info('[BlockchainConfirmation] Found pending deposits', ['count' => $pending->count(), 'current_block' => $currentBlock]);
            foreach ($pending as $deposit) {
                Log::info('[BlockchainConfirmation] Checking deposit', ['deposit_id' => $deposit->id, 'tx_hash' => $deposit->tx_hash, 'block_number' => $deposit->block_number]);
                try {
                    if ($currentBlock === null || $deposit->block_number === null) {
                        // can't compute confirmations
                        continue;
                    }

                    $newConfirmations = max(0, $currentBlock - (int)$deposit->block_number + 1);

                    if ($deposit->confirmations !== $newConfirmations || $deposit->status !== ($newConfirmations >= $threshold ? 'confirmed' : 'pending')) {
                        DB::transaction(function () use ($deposit, $newConfirmations, $balanceService) {
                            // Read threshold from config inside closure to avoid undefined-variable warnings
                            $thresholdLocal = (int) config('ethereum.confirmation_threshold');

                            $deposit->confirmations = $newConfirmations;

                            $shouldConfirm = $newConfirmations >= $thresholdLocal;
                            if ($shouldConfirm) {
                                $deposit->status = 'confirmed';
                                Log::info('Deposit confirmed', ['deposit_id' => $deposit->id, 'tx_hash' => $deposit->tx_hash, 'wallet_id' => $deposit->wallet_id]);
                            }

                            $deposit->save();

                            // If it became confirmed, ensure the matching transaction is also marked confirmed
                            if ($deposit->status === 'confirmed') {
                                Transaction::where(function ($query) use ($deposit) {
                                        $query->where('tx_hash', $deposit->tx_hash)
                                              ->orWhere('reference', $deposit->tx_hash);
                                    })
                                    ->where('type', 'deposit')
                                    ->whereIn('status', ['pending', 'processing'])
                                    ->update(['status' => 'confirmed']);
                            }

                            // If it became confirmed, trigger balance sync for its wallet
                            if ($deposit->status === 'confirmed') {
                                $wallet = Wallet::find($deposit->wallet_id);
                                if ($wallet) {
                                    try {
                                        $balanceService->syncWallet($wallet);
                                        Log::info('Wallet balance updated after confirmation', ['wallet_id' => $wallet->id, 'balance' => $wallet->balance]);
                                    } catch (\Throwable $e) {
                                        Log::error('Balance sync failed for wallet ' . $deposit->wallet_id . ': ' . $e->getMessage());
                                    }
                                }
                            }
                        });
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed while updating confirmations for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
                }
            }

            // Update outgoing withdraw transactions with a tx_hash based on on-chain receipts.
            $withdrawals = Transaction::whereIn('type', ['withdraw', 'withdrawal'])
                ->whereNotNull('tx_hash')
                ->whereIn('status', ['processing', 'pending', 'failed'])
                ->get();

            Log::info('[BlockchainConfirmation] Found withdraw transactions to check', ['count' => $withdrawals->count()]);

            foreach ($withdrawals as $withdrawal) {
                try {
                    Log::info('Checking withdrawal on-chain', ['transaction_id' => $withdrawal->id, 'tx_hash' => $withdrawal->tx_hash, 'db_status' => $withdrawal->status, 'currency' => $withdrawal->currency]);

                    $txHash = $withdrawal->tx_hash;
                    if (empty($txHash)) {
                        continue;
                    }

                    $receiptResult = $ethService->getTransactionReceipt($txHash);
                    $receipt = $receiptResult['receipt'] ?? null;
                    $receiptConfirmations = isset($receiptResult['confirmations']) ? (int)$receiptResult['confirmations'] : null;
                    $receiptBlockNumber = null;

                                        // If no receipt found on-chain yet, do not modify DB — keep transaction pending and do not re-broadcast
                                        if ($receipt === null) {
                                            // nothing to update yet
                                            continue;
                                        }

                    if (is_array($receipt) && isset($receipt['blockNumber'])) {
                        $blockValue = $receipt['blockNumber'];
                        if (is_string($blockValue) && str_starts_with($blockValue, '0x')) {
                            $receiptBlockNumber = hexdec($blockValue);
                        } else {
                            $receiptBlockNumber = (int)$blockValue;
                        }
                    }

                    if ($receiptConfirmations === null && $currentBlock !== null && $receiptBlockNumber !== null) {
                        $receiptConfirmations = max(0, $currentBlock - $receiptBlockNumber + 1);
                    }

                    $receiptConfirmations = $receiptConfirmations ?? 0;
                    $receiptStatus = null;
                    if (is_array($receipt) && array_key_exists('status', $receipt)) {
                        $receiptStatus = $receipt['status'];
                    }

                    $newStatus = 'pending';
                    if (is_array($receipt)) {
                        if ($receiptStatus === 0 || $receiptStatus === '0' || $receiptStatus === false) {
                            $newStatus = 'failed';
                        } else {
                            $thresholdLocal = (int) config('ethereum.confirmation_threshold');
                            if ($receiptConfirmations >= $thresholdLocal) {
                                // Use project's convention for final state (completed preferred in repository migrations/services)
                                $newStatus = 'completed';
                            }
                        }
                    }

                    $updated = false;
                    if ($withdrawal->block_number !== $receiptBlockNumber) {
                        $withdrawal->block_number = $receiptBlockNumber;
                        $updated = true;
                    }
                    if ($withdrawal->confirmations !== $receiptConfirmations) {
                        $withdrawal->confirmations = $receiptConfirmations;
                        $updated = true;
                    }
                    if ($withdrawal->status !== $newStatus) {
                        $withdrawal->status = $newStatus;
                        $updated = true;
                    }

                    if ($updated) {
                        $withdrawal->save();
                        Log::info('Withdraw transaction updated from receipt', ['transaction_id' => $withdrawal->id, 'tx_hash' => $txHash, 'status' => $withdrawal->status, 'confirmations' => $withdrawal->confirmations, 'block_number' => $withdrawal->block_number]);

                        if ($newStatus === 'completed' && $withdrawal->wallet) {
                            try {
                                $balanceService->syncWallet($withdrawal->wallet);
                            } catch (\Throwable $e) {
                                Log::error('Balance sync failed for wallet ' . ($withdrawal->wallet->id ?? '?') . ': ' . $e->getMessage());
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed while updating withdraw confirmations for transaction ' . ($withdrawal->id ?? '?') . ': ' . $e->getMessage());
                }
            }

            Log::info('Confirmation updater job finished', ['checked_deposits' => $pending->count(), 'checked_withdrawals' => $withdrawals->count()]);
        } catch (\Throwable $e) {
            Log::error('Confirmation updater job failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
