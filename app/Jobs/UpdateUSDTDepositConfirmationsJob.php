<?php

namespace App\Jobs;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Services\BalanceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\EthereumService;
use Illuminate\Support\Facades\DB;

class UpdateUSDTDepositConfirmationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct()
    {
    }

    public function handle(EthereumService $ethService, BalanceSyncService $balanceService)
    {
        Log::info('USDT Confirmation updater job started');

        try {
            $currentBlock = null;
            try {
                $currentBlock = $ethService->getCurrentBlockNumber();
            } catch (\Throwable $e) {
                Log::error('Failed to get current block number for USDT confirmations update: ' . $e->getMessage());
            }

            $threshold = (int) config('ethereum.confirmation_threshold');
            $this->processPendingUsdtDeposits($ethService, $balanceService, $currentBlock, $threshold);

            Log::info('USDT Confirmation updater job finished');
        } catch (\Throwable $e) {
            Log::error('USDT Confirmation updater job failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    protected function processPendingUsdtDeposits(EthereumService $ethService, BalanceSyncService $balanceService, ?int $currentBlock, int $threshold): void
    {
        $recheckLimit = (int) config('ethereum.canonical_recheck_blocks', 64);
        $pending = Deposit::whereIn('status', ['pending', 'confirmed'])
            ->where('currency', 'USDT')
            ->whereNotNull('tx_hash')
            ->get();

        foreach ($pending as $deposit) {
            try {
                $txHash = $deposit->tx_hash;
                if (empty($txHash)) {
                    continue;
                }

                $shouldRecheck = false;
                if ($deposit->status === 'pending' && $deposit->block_number !== null) {
                    $shouldRecheck = true;
                }

                if ($deposit->status === 'confirmed' && $currentBlock !== null && $deposit->block_number !== null && $recheckLimit > 0) {
                    $shouldRecheck = (($currentBlock - (int) $deposit->block_number) <= $recheckLimit);
                }

                if (!$shouldRecheck) {
                    continue;
                }

                Log::info('USDT deposit confirmation validation started', [
                    'deposit_id' => $deposit->id,
                    'tx_hash' => $txHash,
                    'current_status' => $deposit->status,
                    'block_number' => $deposit->block_number,
                    'stored_confirmations' => $deposit->confirmations,
                    'currentBlock' => $currentBlock,
                    'threshold' => $threshold,
                    'stored_block_hash' => $deposit->block_hash ?? null,
                    'stored_transaction_index' => $deposit->transaction_index ?? null,
                ]);

                $validation = $this->validateCanonicalTransaction(
                    $ethService,
                    $txHash,
                    $deposit->block_hash,
                    $deposit->block_number,
                    $deposit->transaction_index,
                    'deposit'
                );

                Log::info('USDT deposit canonical validation result', [
                    'deposit_id' => $deposit->id,
                    'tx_hash' => $txHash,
                    'validation_result' => $validation['result'] ?? null,
                    'receipt_status' => $validation['receipt_status'] ?? ($validation['receipt']['status'] ?? null),
                    'current_block_hash' => $validation['current_block_hash'] ?? null,
                    'transaction_index' => $validation['transaction_index'] ?? null,
                    'receipt_blockNumber' => $validation['receipt']['blockNumber'] ?? null,
                    'receipt_blockHash' => $validation['receipt']['blockHash'] ?? null,
                ]);

                if ($validation['result'] === 'pending' || $validation['result'] === 'retryable') {
                    $deposit->canonical_checked_at = now();
                    $deposit->save();
                    continue;
                }

                if ($validation['result'] === 'reorged') {
                    $this->markDepositReorged($deposit, $validation, $balanceService, $wallet = $deposit->wallet);
                    continue;
                }

                if ($validation['result'] === 'failed') {
                    $deposit->receipt_status = '0';
                    $deposit->status = 'pending';
                    $deposit->canonical_checked_at = now();
                    $deposit->save();
                    continue;
                }

                $receiptStatus = $validation['receipt_status'] ?? $ethService->normalizeReceiptStatus($validation['receipt']['status'] ?? null);
                if ($receiptStatus === null) {
                    $deposit->canonical_checked_at = now();
                    $deposit->save();
                    continue;
                }

                if ($currentBlock === null || $deposit->block_number === null) {
                    $deposit->canonical_checked_at = now();
                    $deposit->save();
                    continue;
                }

                $newConfirmations = max(0, $currentBlock - (int) $deposit->block_number + 1);
                $deposit->block_hash = $validation['current_block_hash'] ?? $deposit->block_hash;
                $deposit->transaction_index = $validation['transaction_index'] ?? $deposit->transaction_index;
                $deposit->receipt_status = '1';
                $deposit->confirmations = $newConfirmations;
                $deposit->canonical_checked_at = now();

                if ($newConfirmations >= $threshold && $receiptStatus === true) {
                    $deposit->status = 'confirmed';
                    $deposit->confirmed_at = $deposit->confirmed_at ?? now();
                    $deposit->processed_at = $deposit->processed_at ?? now();
                } else {
                    $deposit->status = 'pending';
                    $deposit->confirmed_at = null;
                }

                // Persist deposit and, if confirmed, update matching Transactions atomically to avoid inconsistency
                DB::transaction(function () use ($deposit, $balanceService) {
                    $deposit->save();
                    if ($deposit->status === 'confirmed') {
                        $this->syncDepositTransactionMetadata($deposit);
                    }
                });

                if ($deposit->status === 'confirmed') {
                    if ($deposit->wallet) {
                        try {
                            $balanceService->syncWallet($deposit->wallet);
                        } catch (\Throwable $e) {
                            Log::error('Balance sync failed for confirmed USDT deposit', ['wallet_id' => $deposit->wallet->id ?? null, 'deposit_id' => $deposit->id, 'exception' => $e->getMessage()]);
                        }
                    }
                }

            } catch (\Throwable $e) {
                Log::error('USDT deposit confirmation check failed for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
            }
        }
    }

    protected function syncDepositTransactionMetadata(Deposit $deposit): void
    {
        if (empty($deposit->tx_hash)) {
            return;
        }

        $walletAddress = $deposit->wallet ? $deposit->wallet->wallet_address : null;

        Transaction::where(function ($query) use ($deposit) {
                $query->where('tx_hash', $deposit->tx_hash)
                    ->orWhere('reference', $deposit->tx_hash);
            })
            ->where('type', 'deposit')
            ->update([
                'tx_hash' => $deposit->tx_hash,
                'block_number' => $deposit->block_number,
                'confirmations' => $deposit->confirmations ?? 0,
                'receiver_wallet_address' => $walletAddress,
                'status' => $deposit->status,
                'confirmed_at' => $deposit->status === 'confirmed' ? ($deposit->confirmed_at ?? now()) : null,
                'updated_at' => now(),
            ]);
    }

    protected function validateCanonicalTransaction(EthereumService $ethService, string $txHash, ?string $storedBlockHash, ?int $storedBlockNumber, ?int $storedTransactionIndex, string $kind): array
    {
        try {
            $receiptResult = $ethService->getTransactionReceipt($txHash);
        } catch (\Throwable $e) {
            Log::warning('USDT canonical validation receipt RPC error', ['tx_hash' => $txHash, 'kind' => $kind, 'operation' => 'getTransactionReceipt', 'error' => $e->getMessage()]);
            return ['result' => 'retryable'];
        }

        $receipt = is_array($receiptResult) ? ($receiptResult['receipt'] ?? null) : null;
        if ($receipt === null) {
            Log::info('USDT canonical validation receipt not found', ['tx_hash' => $txHash, 'kind' => $kind]);
            return ['result' => 'pending'];
        }

        $status = $ethService->normalizeReceiptStatus($receipt['status'] ?? null);
        if ($status === null) {
            Log::info('USDT canonical validation receipt status unavailable', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt' => $receipt]);
            return ['result' => 'pending', 'receipt' => $receipt];
        }

        if ($status === false) {
            Log::info('USDT canonical validation transaction failed', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt' => $receipt]);
            return ['result' => 'failed', 'receipt' => $receipt];
        }

        $blockNumber = $this->extractBlockNumber($receipt['blockNumber'] ?? $storedBlockNumber);
        if ($blockNumber === null) {
            Log::info('USDT canonical validation block number unavailable', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt' => $receipt]);
            return ['result' => 'pending', 'receipt' => $receipt];
        }

        try {
            $canonicalBlock = $ethService->getBlock($blockNumber);
        } catch (\Throwable $e) {
            Log::warning('USDT canonical validation block RPC error', ['tx_hash' => $txHash, 'kind' => $kind, 'operation' => 'getBlock', 'block_number' => $blockNumber, 'error' => $e->getMessage()]);
            return ['result' => 'retryable'];
        }

        $canonicalHash = $canonicalBlock['block']['hash'] ?? $canonicalBlock['hash'] ?? null;
        if (empty($canonicalHash)) {
            Log::warning('USDT canonical validation canonical block hash unavailable', ['tx_hash' => $txHash, 'kind' => $kind, 'block_number' => $blockNumber]);
            return ['result' => 'retryable'];
        }

        $receiptHash = $receipt['blockHash'] ?? $storedBlockHash;
        if ($storedBlockHash !== null && strtolower((string) $storedBlockHash) !== strtolower((string) $canonicalHash)) {
            Log::warning('USDT canonical validation reorg detected', ['tx_hash' => $txHash, 'kind' => $kind, 'stored_block_hash' => $storedBlockHash, 'current_block_hash' => $canonicalHash]);
            return [
                'result' => 'reorged',
                'reason' => 'stored_block_hash_no_longer_canonical',
                'old_block_hash' => $storedBlockHash,
                'current_block_hash' => $canonicalHash,
                'block_number' => $blockNumber,
                'receipt' => $receipt,
            ];
        }

        if ($receiptHash !== null && strtolower((string) $receiptHash) !== strtolower((string) $canonicalHash)) {
            Log::warning('USDT canonical validation reorg detected (receipt mismatch)', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt_block_hash' => $receiptHash, 'current_block_hash' => $canonicalHash]);
            return [
                'result' => 'reorged',
                'reason' => 'receipt_block_hash_no_longer_canonical',
                'old_block_hash' => $receiptHash,
                'current_block_hash' => $canonicalHash,
                'block_number' => $blockNumber,
                'receipt' => $receipt,
            ];
        }

        $transactionIndex = $this->extractIntegerValue($receipt['transactionIndex'] ?? $storedTransactionIndex);
        if ($storedTransactionIndex !== null && $transactionIndex !== null && (int) $storedTransactionIndex !== (int) $transactionIndex) {
            Log::warning('USDT canonical validation transaction index mismatch', ['tx_hash' => $txHash, 'kind' => $kind, 'stored_transaction_index' => $storedTransactionIndex, 'current_transaction_index' => $transactionIndex]);
            return [
                'result' => 'reorged',
                'reason' => 'transaction_index_mismatch',
                'old_transaction_index' => $storedTransactionIndex,
                'current_transaction_index' => $transactionIndex,
                'block_number' => $blockNumber,
                'current_block_hash' => $canonicalHash,
                'receipt' => $receipt,
            ];
        }

        Log::info('USDT canonical validation successful', ['tx_hash' => $txHash, 'kind' => $kind, 'block_number' => $blockNumber, 'current_block_hash' => $canonicalHash, 'transaction_index' => $transactionIndex]);

        return [
            'result' => 'canonical',
            'current_block_hash' => $canonicalHash,
            'transaction_index' => $transactionIndex,
            'receipt' => $receipt,
            'receipt_status' => $status,
        ];
    }

    protected function extractIntegerValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            if (str_starts_with(strtolower($trimmed), '0x')) {
                return hexdec($trimmed);
            }

            if (is_numeric($trimmed)) {
                return (int) $trimmed;
            }
        }

        return null;
    }

    protected function extractBlockNumber(mixed $value): ?int
    {
        return $this->extractIntegerValue($value);
    }

    protected function markDepositReorged(Deposit $deposit, array $validation, BalanceSyncService $balanceService, $wallet = null): void
    {
        $wallet = $wallet ?? $deposit->wallet;
        $deposit->status = 'reorged';
        $deposit->reorged_at = now();
        $deposit->reorg_reason = $validation['reason'] ?? 'canonical_chain_mismatch';
        $deposit->canonical_checked_at = now();
        $deposit->save();

        Transaction::where(function ($query) use ($deposit) {
            $query->where('tx_hash', $deposit->tx_hash)
                ->orWhere('reference', $deposit->tx_hash);
        })
            ->where('type', 'deposit')
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->update([
                'status' => 'reorged',
                'failure_reason' => $deposit->reorg_reason,
            ]);

        Log::warning('USDT deposit canonical validation failed: reorg detected', [
            'deposit_id' => $deposit->id,
            'tx_hash' => $deposit->tx_hash,
            'wallet_id' => $deposit->wallet_id,
            'old_block_hash' => $validation['old_block_hash'] ?? $deposit->block_hash,
            'current_block_hash' => $validation['current_block_hash'] ?? null,
            'block_number' => $validation['block_number'] ?? $deposit->block_number,
            'reason' => $deposit->reorg_reason,
        ]);

        if ($wallet) {
            try {
                $balanceService->syncWallet($wallet);
            } catch (\Throwable $e) {
                Log::error('Balance sync failed after USDT deposit reorg', ['wallet_id' => $wallet->id, 'deposit_id' => $deposit->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
