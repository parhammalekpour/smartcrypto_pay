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

        // In-job memoization caches to avoid repeated RPC calls for the same tx/block within one run
        protected array $receiptCache = [];
        protected array $blockCache = [];

        // Collect wallets that need a balance sync at the end of the job (avoid repeated syncWallet RPC calls)
        protected array $walletsToSync = [];

        public function __construct()
        {
            // ensure caches start empty per job instance
            $this->receiptCache = [];
            $this->blockCache = [];
            $this->walletsToSync = [];
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

            $threshold = (int) config('ethereum.confirmation_threshold');
            $this->processPendingDeposits($ethService, $balanceService, $currentBlock, $threshold);
            $this->processPendingWithdrawals($ethService, $balanceService, $currentBlock, $threshold);

            // Perform a single balance sync per unique wallet collected during processing
            if (!empty($this->walletsToSync)) {
                foreach ($this->walletsToSync as $walletId => $walletToSync) {
                    try {
                        $oldBalance = $walletToSync->balance ?? null;
                        $syncResult = $balanceService->syncWallet($walletToSync);
                        $newBalance = $syncResult['balance'] ?? null;

                        Log::info('Deferred wallet balance sync completed', [
                            'wallet_id' => $walletId,
                            'wallet_balance_before' => $oldBalance,
                            'wallet_balance_after' => $newBalance,
                            'sync_result' => is_array($syncResult) ? $syncResult : null,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Deferred balance sync failed for wallet ' . ($walletId ?? '?') . ': ' . $e->getMessage());
                    }
                }
            }

            Log::info('Confirmation updater job finished');
        } catch (\Throwable $e) {
            Log::error('Confirmation updater job failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
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

    protected function processPendingDeposits(EthereumService $ethService, BalanceSyncService $balanceService, ?int $currentBlock, int $threshold): void
    {
        $recheckLimit = (int) config('ethereum.canonical_recheck_blocks', 64);
        $limit = (int) config('ethereum.confirmation_scan_limit', 200);
        $pending = Deposit::whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('tx_hash')
            ->orderByRaw('canonical_checked_at IS NULL DESC')
            ->orderBy('canonical_checked_at', 'asc')
            ->limit($limit)
            ->get();

        // First, determine which deposits actually need recheck, so we can batch RPCs
        $toCheck = [];
        foreach ($pending as $deposit) {
            $shouldRecheck = false;
            if ($deposit->status === 'pending' && $deposit->block_number !== null) {
                $shouldRecheck = true;
            }
            if ($deposit->status === 'confirmed' && $currentBlock !== null && $deposit->block_number !== null && $recheckLimit > 0) {
                $shouldRecheck = (($currentBlock - (int) $deposit->block_number) <= $recheckLimit);
            }
            if ($shouldRecheck && !empty($deposit->tx_hash)) {
                $toCheck[] = $deposit;
            }
        }

        // Batch fetch receipts for all toCheck deposits
        $txHashes = array_values(array_unique(array_map(fn($d) => $d->tx_hash, $toCheck)));
        if (!empty($txHashes)) {
            $batchReceipts = $ethService->batchGetTransactionReceipts($txHashes);
            foreach ($batchReceipts as $tx => $res) {
                // maintain same shape as previous getTransactionReceipt return
                if (isset($res['error'])) {
                    // store error marker so validateCanonicalTransaction can treat accordingly
                    $this->receiptCache[$tx] = $res;
                } else {
                    $this->receiptCache[$tx] = ['receipt' => $res['receipt'] ?? null];
                }
            }

            // Collect block numbers from receipts to batch fetch blocks
            $blockNumbers = [];
            foreach ($this->receiptCache as $tx => $r) {
                $receipt = $r['receipt'] ?? null;
                if (is_array($receipt) && isset($receipt['blockNumber'])) {
                    $bn = $this->extractBlockNumber($receipt['blockNumber']);
                    if ($bn !== null) $blockNumbers[] = $bn;
                }
            }
            $blockNumbers = array_values(array_unique($blockNumbers));
            if (!empty($blockNumbers)) {
                $batchBlocks = $ethService->batchGetBlocks($blockNumbers);
                foreach ($batchBlocks as $bn => $bres) {
                    if (isset($bres['error'])) {
                        $this->blockCache[$bn] = $bres;
                    } else {
                        $this->blockCache[$bn] = ['block' => $bres['block'] ?? null];
                    }
                }
            }
        }

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

                Log::info('Deposit confirmation validation started', [
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

                Log::info('Deposit canonical validation result', [
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

                if ($receiptStatus === false) {
                    $deposit->receipt_status = '0';
                    $deposit->status = 'pending';
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

                if ($newConfirmations >= $threshold) {
                    $deposit->status = 'confirmed';
                    $deposit->confirmed_at = now();
                    $deposit->processed_at = $deposit->processed_at ?? now();
                } else {
                    $deposit->status = 'pending';
                    $deposit->confirmed_at = null;
                }

                // Persist deposit and, if confirmed, update matching Transactions atomically to avoid inconsistency
                $transactionsUpdated = 0;
                try {
                    DB::transaction(function () use ($deposit, &$transactionsUpdated) {
                        $deposit->save();

                        if ($deposit->status === 'confirmed') {
                            // Update transaction metadata/status in the same DB transaction to keep in sync
                            $transactionsUpdated = Transaction::where(function ($query) use ($deposit) {
                                $query->where('tx_hash', $deposit->tx_hash)
                                    ->orWhere('reference', $deposit->tx_hash);
                            })
                                ->where('type', 'deposit')
                                ->whereIn('status', ['pending', 'processing'])
                                ->update([
                                    'status' => 'confirmed',
                                    'tx_hash' => $deposit->tx_hash,
                                    'block_number' => $deposit->block_number,
                                    'block_hash' => $deposit->block_hash,
                                    'transaction_index' => $deposit->transaction_index,
                                    'receipt_status' => $deposit->receipt_status,
                                    'confirmations' => $deposit->confirmations ?? 0,
                                    'confirmed_at' => $deposit->confirmed_at ?? now(),
                                    'receiver_wallet_address' => $deposit->wallet ? $deposit->wallet->wallet_address : null,
                                ]);
                        }
                    });
                } catch (\Throwable $e) {
                    Log::error('Failed to persist deposit + transaction update atomically: ' . $e->getMessage(), ['deposit_id' => $deposit->id]);
                    // Save deposit at least (best-effort) if txn failed
                    try { $deposit->save(); } catch (\Throwable $_) { /* ignore */ }
                }

                Log::info('Deposit and transaction confirmation persisted', [
                    'deposit_id' => $deposit->id,
                    'tx_hash' => $deposit->tx_hash,
                    'deposit_status' => $deposit->status,
                    'transactions_updated' => $transactionsUpdated,
                ]);

                if ($deposit->status === 'confirmed' && $transactionsUpdated === 0) {
                    // Investigate matching transactions if none were updated
                    $matches = Transaction::where(function ($query) use ($deposit) {
                        $query->where('tx_hash', $deposit->tx_hash)
                            ->orWhere('reference', $deposit->tx_hash);
                    })->where('type', 'deposit')->get();

                    $matchingCount = $matches->count();
                    $matchingStatuses = $matches->pluck('status')->unique()->values()->all();

                    Log::warning('Deposit confirmed but matching transaction was not updated', [
                        'deposit_id' => $deposit->id,
                        'tx_hash' => $deposit->tx_hash,
                        'matching_transaction_count' => $matchingCount,
                        'matching_transaction_statuses' => $matchingStatuses,
                    ]);
                }

                if ($deposit->status === 'confirmed') {
                    $wallet = $deposit->wallet;
                    if ($wallet) {
                        try {
                            // Defer balance sync to end of job to avoid repeated RPCs for the same wallet
                            $oldBalance = $wallet->balance ?? null;
                            // collect the wallet for a single sync at the end of the run
                            $this->walletsToSync[$wallet->id] = $wallet;

                            Log::info('Deposit confirmed - wallet queued for balance sync (deferred)', [
                                'wallet_id' => $wallet->id,
                                'deposit_id' => $deposit->id,
                                'tx_hash' => $deposit->tx_hash,
                                'wallet_balance_before' => $oldBalance,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error('Balance sync failed for wallet ' . ($wallet->id ?? '?') . ': ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed while updating confirmations for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage(), ['tx_hash' => $deposit->tx_hash ?? null]);
            }
        }
    }

    protected function processPendingWithdrawals(EthereumService $ethService, BalanceSyncService $balanceService, ?int $currentBlock, int $threshold): void
    {
        $limit = (int) config('ethereum.confirmation_scan_limit', 200);
        $pendingOutbound = Transaction::whereIn('type', ['withdraw', 'withdrawal'])
            ->whereNotNull('tx_hash')
            ->whereIn('status', ['processing', 'pending', 'broadcasting', 'completed'])
            ->orderByRaw('last_checked_at IS NULL DESC')
            ->orderBy('last_checked_at', 'asc')
            ->limit($limit)
            ->get();

        // Pre-batch receipts/blocks for withdrawals to reduce RPC calls
        $toCheckOut = [];
        foreach ($pendingOutbound as $withdrawal) {
            if (in_array($withdrawal->status, ['failed', 'dropped', 'reorged', 'replaced'], true)) {
                continue;
            }
            if (empty($withdrawal->tx_hash)) continue;
            $toCheckOut[] = $withdrawal;
        }

        $txHashesOut = array_values(array_unique(array_map(fn($w) => $w->tx_hash, $toCheckOut)));
        if (!empty($txHashesOut)) {
            $batchReceiptsOut = $ethService->batchGetTransactionReceipts($txHashesOut);
            foreach ($batchReceiptsOut as $tx => $res) {
                if (isset($res['error'])) {
                    $this->receiptCache[$tx] = $res;
                } else {
                    $this->receiptCache[$tx] = ['receipt' => $res['receipt'] ?? null];
                }
            }

            $blockNumbersOut = [];
            foreach ($this->receiptCache as $tx => $r) {
                $receipt = $r['receipt'] ?? null;
                if (is_array($receipt) && isset($receipt['blockNumber'])) {
                    $bn = $this->extractBlockNumber($receipt['blockNumber']);
                    if ($bn !== null) $blockNumbersOut[] = $bn;
                }
            }
            $blockNumbersOut = array_values(array_unique($blockNumbersOut));
            if (!empty($blockNumbersOut)) {
                $batchBlocksOut = $ethService->batchGetBlocks($blockNumbersOut);
                foreach ($batchBlocksOut as $bn => $bres) {
                    if (isset($bres['error'])) {
                        $this->blockCache[$bn] = $bres;
                    } else {
                        $this->blockCache[$bn] = ['block' => $bres['block'] ?? null];
                    }
                }
            }
        }

        foreach ($pendingOutbound as $withdrawal) {
            try {
                $txHash = $withdrawal->tx_hash;
                if (empty($txHash)) {
                    continue;
                }

                if (in_array($withdrawal->status, ['failed', 'dropped', 'reorged', 'replaced'], true)) {
                    continue;
                }

                // Detailed debug log for withdrawals to trace confirmation path
                Log::info('Processing pending outbound transaction for confirmation', [
                    'transaction_id' => $withdrawal->id,
                    'tx_hash' => $txHash,
                    'stored_block_number' => $withdrawal->block_number,
                    'stored_block_hash' => $withdrawal->block_hash,
                    'stored_transaction_index' => $withdrawal->transaction_index,
                    'status' => $withdrawal->status,
                    'broadcasted_at' => $withdrawal->broadcasted_at ?? null,
                    'created_at' => $withdrawal->created_at ?? null,
                ]);

                $validation = $this->validateCanonicalTransaction(
                    $ethService,
                    $txHash,
                    $withdrawal->block_hash,
                    $withdrawal->block_number,
                    $withdrawal->transaction_index,
                    'withdrawal'
                );

                Log::info('Withdrawal canonical validation result', [
                    'transaction_id' => $withdrawal->id,
                    'tx_hash' => $txHash,
                    'validation_result' => $validation['result'] ?? null,
                    'receipt_status' => $validation['receipt_status'] ?? ($validation['receipt']['status'] ?? null),
                    'receipt_blockNumber' => $validation['receipt']['blockNumber'] ?? null,
                    'receipt_blockHash' => $validation['receipt']['blockHash'] ?? null,
                    'current_block_hash' => $validation['current_block_hash'] ?? null,
                    'transaction_index' => $validation['transaction_index'] ?? null,
                ]);

                if ($validation['result'] === 'pending') {
                    $txLookup = null;
                    try {
                        $txLookup = $ethService->getTransactionByHash($txHash);
                    } catch (\Throwable $e) {
                        Log::warning('Pending outbound tx lookup failed', ['transaction_id' => $withdrawal->id, 'tx_hash' => $txHash, 'error' => $e->getMessage()]);
                    }

                    $knownAt = $withdrawal->broadcasted_at ?? $withdrawal->created_at;
                    $ageSeconds = $knownAt ? abs(now()->diffInSeconds($knownAt)) : 0;
                    $pendingTimeout = (int) config('ethereum.pending_timeout', 1800);

                    if (($txLookup === null || empty($txLookup['transaction'])) && $ageSeconds > $pendingTimeout) {
                        $withdrawal->status = 'dropped';
                        $withdrawal->failure_reason = 'transaction_missing_from_provider_after_timeout';
                        $withdrawal->failed_at = now();
                        $withdrawal->last_checked_at = now();
                        $withdrawal->save();
                        Log::warning('Pending outbound transaction marked dropped after timeout', ['transaction_id' => $withdrawal->id, 'tx_hash' => $txHash, 'age_seconds' => $ageSeconds]);
                    } else {
                        $withdrawal->status = 'pending';
                        $withdrawal->last_checked_at = now();
                        $withdrawal->save();
                    }
                    continue;
                }

                if ($validation['result'] === 'pending' || $validation['result'] === 'retryable') {
                    $withdrawal->last_checked_at = now();
                    $withdrawal->save();
                    continue;
                }

                if ($validation['result'] === 'reorged') {
                    $this->markWithdrawalReorged($withdrawal, $validation, $balanceService);
                    continue;
                }

                if ($validation['result'] === 'failed') {
                    $withdrawal->status = 'failed';
                    $withdrawal->failure_reason = 'receipt_status_failed';
                    $withdrawal->receipt_status = '0';
                    $withdrawal->failed_at = $withdrawal->failed_at ?? now();
                    $withdrawal->last_checked_at = now();
                    $withdrawal->save();
                    if ($withdrawal->wallet) {
                        // Defer balance sync to end of job to avoid repeated RPCs for the same wallet
                        $this->walletsToSync[$withdrawal->wallet->id] = $withdrawal->wallet;
                    }
                    continue;
                }

                $this->detectSameNonceReplacement($withdrawal);

                $receiptStatus = $validation['receipt_status'] ?? $ethService->normalizeReceiptStatus($validation['receipt']['status'] ?? null);
                if ($receiptStatus === null) {
                    $withdrawal->last_checked_at = now();
                    $withdrawal->save();
                    continue;
                }

                $blockNumber = $this->extractBlockNumber($validation['receipt']['blockNumber'] ?? $withdrawal->block_number);
                $transactionIndex = $this->extractIntegerValue($validation['receipt']['transactionIndex'] ?? $withdrawal->transaction_index);
                $confirmations = $currentBlock !== null && $blockNumber !== null ? max(0, $currentBlock - $blockNumber + 1) : 0;

                $withdrawal->block_number = $blockNumber;
                $withdrawal->block_hash = $validation['current_block_hash'] ?? ($validation['receipt']['blockHash'] ?? $withdrawal->block_hash);
                $withdrawal->transaction_index = $transactionIndex;
                $withdrawal->receipt_status = $receiptStatus ? '1' : '0';
                $withdrawal->confirmations = $confirmations;
                $withdrawal->last_checked_at = now();

                if ($receiptStatus === false) {
                    $withdrawal->status = 'failed';
                    $withdrawal->failed_at = $withdrawal->failed_at ?? now();
                    $withdrawal->failure_reason = 'receipt_status_failed';
                } elseif ($receiptStatus === true && $confirmations >= $threshold) {
                    $withdrawal->status = 'completed';
                    $withdrawal->confirmed_at = $withdrawal->confirmed_at ?? now();
                } else {
                    $withdrawal->status = 'pending';
                }

                $withdrawal->save();

                if (($withdrawal->status === 'failed' || $withdrawal->status === 'completed') && $withdrawal->wallet) {
                    // Defer balance sync to end of job to avoid repeated RPCs for the same wallet
                    $this->walletsToSync[$withdrawal->wallet->id] = $withdrawal->wallet;
                }
            } catch (\Throwable $e) {
                Log::error('Failed while checking pending outbound tx ' . ($withdrawal->id ?? '?') . ': ' . $e->getMessage());
            }
        }
    }

    protected function validateCanonicalTransaction(EthereumService $ethService, string $txHash, ?string $storedBlockHash, ?int $storedBlockNumber, ?int $storedTransactionIndex, string $kind): array
    {
            // Memoize receipts per txHash (avoid repeated RPC during this job run)
            if (isset($this->receiptCache[$txHash])) {
                $receiptResult = $this->receiptCache[$txHash];
                // If batch populated an error marker, treat as retryable so caller can retry later
                if (is_array($receiptResult) && isset($receiptResult['error'])) {
                    Log::warning('Canonical validation cached receipt contains error - treating as retryable', ['tx_hash' => $txHash, 'kind' => $kind, 'error' => $receiptResult['error']]);
                    return ['result' => 'retryable'];
                }
            } else {
                try {
                    $receiptResult = $ethService->getTransactionReceipt($txHash);
                    $this->receiptCache[$txHash] = $receiptResult;
                } catch (\Throwable $e) {
                    Log::warning('Canonical validation receipt RPC error', ['tx_hash' => $txHash, 'kind' => $kind, 'operation' => 'getTransactionReceipt', 'error' => $e->getMessage()]);
                    return ['result' => 'retryable'];
                }
        }

            $receipt = is_array($receiptResult) ? ($receiptResult['receipt'] ?? null) : null;
            if ($receipt === null) {
                Log::info('Canonical validation receipt not found', ['tx_hash' => $txHash, 'kind' => $kind]);
                return ['result' => 'pending'];
        }

            $status = $ethService->normalizeReceiptStatus($receipt['status'] ?? null);
            if ($status === null) {
                Log::info('Canonical validation receipt status unavailable', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt' => $receipt]);
                return ['result' => 'pending', 'receipt' => $receipt];
            }

            if ($status === false) {
                Log::info('Canonical validation transaction failed', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt' => $receipt]);
                return ['result' => 'failed', 'receipt' => $receipt];
        }

            $blockNumber = $this->extractBlockNumber($receipt['blockNumber'] ?? $storedBlockNumber);
            if ($blockNumber === null) {
                Log::info('Canonical validation block number unavailable', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt' => $receipt]);
                return ['result' => 'pending', 'receipt' => $receipt];
            }

            // Memoize blocks per blockNumber to avoid repeated getBlock RPCs
            if (isset($this->blockCache[$blockNumber])) {
                $canonicalBlock = $this->blockCache[$blockNumber];
                if (is_array($canonicalBlock) && isset($canonicalBlock['error'])) {
                    Log::warning('Canonical validation cached block contains error - treating as retryable', ['tx_hash' => $txHash, 'kind' => $kind, 'block_number' => $blockNumber, 'error' => $canonicalBlock['error']]);
                    return ['result' => 'retryable'];
                }
            } else {
                try {
                    $canonicalBlock = $ethService->getBlock($blockNumber);
                    $this->blockCache[$blockNumber] = $canonicalBlock;
                } catch (\Throwable $e) {
                    Log::warning('Canonical validation block RPC error', ['tx_hash' => $txHash, 'kind' => $kind, 'operation' => 'getBlock', 'block_number' => $blockNumber, 'error' => $e->getMessage()]);
                    return ['result' => 'retryable'];
                }
            }

            $canonicalHash = $canonicalBlock['block']['hash'] ?? $canonicalBlock['hash'] ?? null;
            if (empty($canonicalHash)) {
                Log::warning('Canonical validation canonical block hash unavailable', ['tx_hash' => $txHash, 'kind' => $kind, 'block_number' => $blockNumber]);
                return ['result' => 'retryable'];
            }

            $receiptHash = $receipt['blockHash'] ?? $storedBlockHash;
            if ($storedBlockHash !== null && strtolower((string) $storedBlockHash) !== strtolower((string) $canonicalHash)) {
                Log::warning('Canonical validation reorg detected', ['tx_hash' => $txHash, 'kind' => $kind, 'stored_block_hash' => $storedBlockHash, 'current_block_hash' => $canonicalHash]);
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
                Log::warning('Canonical validation reorg detected (receipt mismatch)', ['tx_hash' => $txHash, 'kind' => $kind, 'receipt_block_hash' => $receiptHash, 'current_block_hash' => $canonicalHash]);
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
                Log::warning('Canonical validation transaction index mismatch', ['tx_hash' => $txHash, 'kind' => $kind, 'stored_transaction_index' => $storedTransactionIndex, 'current_transaction_index' => $transactionIndex]);
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

            Log::info('Canonical validation successful', ['tx_hash' => $txHash, 'kind' => $kind, 'block_number' => $blockNumber, 'current_block_hash' => $canonicalHash, 'transaction_index' => $transactionIndex]);

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

        Log::warning('Deposit canonical validation failed: reorg detected', [
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
                Log::error('Balance sync failed after deposit reorg', ['wallet_id' => $wallet->id, 'deposit_id' => $deposit->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function markWithdrawalReorged(Transaction $withdrawal, array $validation, BalanceSyncService $balanceService): void
    {
        $withdrawal->status = 'reorged';
        $withdrawal->failure_reason = $validation['reason'] ?? 'canonical_chain_mismatch';
        $withdrawal->last_checked_at = now();
        $withdrawal->save();

        Log::warning('Withdrawal canonical validation failed: reorg detected', [
            'transaction_id' => $withdrawal->id,
            'tx_hash' => $withdrawal->tx_hash,
            'wallet_id' => $withdrawal->wallet_id,
            'old_block_hash' => $validation['old_block_hash'] ?? $withdrawal->block_hash,
            'current_block_hash' => $validation['current_block_hash'] ?? null,
            'block_number' => $validation['block_number'] ?? $withdrawal->block_number,
            'reason' => $withdrawal->failure_reason,
        ]);

        if ($withdrawal->wallet) {
            try {
                $balanceService->syncWallet($withdrawal->wallet);
            } catch (\Throwable $e) {
                Log::error('Balance sync failed after withdrawal reorg', ['wallet_id' => $withdrawal->wallet->id, 'transaction_id' => $withdrawal->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function detectSameNonceReplacement(Transaction $withdrawal): void
    {
        if ($withdrawal->wallet_id === null || $withdrawal->nonce === null || empty($withdrawal->tx_hash)) {
            return;
        }

        $replacement = Transaction::where('wallet_id', $withdrawal->wallet_id)
            ->where('nonce', $withdrawal->nonce)
            ->whereNotNull('tx_hash')
            ->where('tx_hash', '!=', $withdrawal->tx_hash)
            ->orderByDesc('id')
            ->first();

        if (!$replacement) {
            return;
        }

        if ($withdrawal->replaced_by !== $replacement->tx_hash) {
            $withdrawal->replaced_by = $replacement->tx_hash;
            $withdrawal->status = 'replaced';
            $withdrawal->failure_reason = 'same_nonce_replacement_detected';
        }

        if ($replacement->replacement_of !== $withdrawal->tx_hash) {
            $replacement->replacement_of = $withdrawal->tx_hash;
        }

        $withdrawal->save();
        $replacement->save();

        Log::warning('Replacement transaction detected for same nonce', [
            'original_tx_hash' => $withdrawal->tx_hash,
            'replacement_tx_hash' => $replacement->tx_hash,
            'wallet_id' => $withdrawal->wallet_id,
            'nonce' => $withdrawal->nonce,
        ]);
    }
}
