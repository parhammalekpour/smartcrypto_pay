<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BlockchainDepositService
{
    protected EthereumService $ethService;
    protected int $confirmationThreshold;

    protected BalanceSyncService $balanceService;

    public function __construct(EthereumService $ethService)
    {
        $this->ethService = $ethService;
        $this->balanceService = new BalanceSyncService();

        // Confirmation threshold: use centralized config to avoid duplication
        $this->confirmationThreshold = (int) config('ethereum.confirmation_threshold');
    }

    /**
     * Scan wallets for incoming deposit transfers.
     * - Scans ETH wallets and, when configured, USDT wallets.
     * - Prevents duplicate deposits by tx_hash/currency constraint
     * - Stores user_id or merchant_id from wallet owner_type/owner_id (nullable)
     *
     * @param int $limitPerWallet Number of recent tx to check per wallet
     * @return array Summary: ['created' => n, 'skipped' => m, 'errors' => [...]]
     */
    public function scanOnce(int $limitPerWallet = 20, ?int $singleWalletId = null, ?int $fromBlock = null, ?int $toBlock = null): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];
        $targetedHistoricalScan = $fromBlock !== null || $toBlock !== null;

        if ($targetedHistoricalScan) {
            if ($fromBlock === null || $toBlock === null) {
                throw new \InvalidArgumentException('Targeted historical scan requires both fromBlock and toBlock values.');
            }
            if ($fromBlock < 0 || $toBlock < 0) {
                throw new \InvalidArgumentException('Targeted historical scan block values must be >= 0.');
            }
            if ($fromBlock > $toBlock) {
                throw new \InvalidArgumentException('Targeted historical scan requires fromBlock <= toBlock.');
            }
        }

        // Determine which currencies we can scan in this environment.
        $currencies = ['ETH'];
        if (!empty(env('USDT_CONTRACT_ADDRESS'))) {
            $currencies[] = 'USDT';
        }

        $query = Wallet::whereIn('currency', $currencies);
        if ($singleWalletId !== null) {
            $query->where('id', $singleWalletId);
        } else {
            // Limit number of wallets processed per scan to avoid long-running jobs
            $walletsPerJob = (int) config('ethereum.scan_wallets_per_job', 20);
            // Prioritize wallets that were never scanned or scanned longest ago
            $query->orderByRaw('last_scanned_block IS NULL DESC')
                  ->orderBy('last_scanned_block', 'asc')
                  ->limit($walletsPerJob);
        }

        $wallets = $query->get();

        // Current block for confirmations
        try {
            $currentBlock = $this->ethService->getCurrentBlockNumber();
        } catch (\Throwable $e) {
            Log::error('Failed to get current block number: ' . $e->getMessage());
            $currentBlock = null;
        }

        foreach ($wallets as $wallet) {
            try {
                $address = $wallet->wallet_address;
                if (empty($address)) continue;

                $scanWindowFrom = $targetedHistoricalScan ? $fromBlock : null;
                $scanWindowTo = $targetedHistoricalScan ? $toBlock : null;

                // Use last_scanned_block to limit how far back we query, unless a targeted historical scan was explicitly requested.
                if (!$targetedHistoricalScan) {
                    $scanWindowFrom = null;
                    if (!empty($wallet->last_scanned_block) && is_numeric($wallet->last_scanned_block)) {
                        $scanWindowFrom = $wallet->last_scanned_block + 1;
                    }
                }

                if (strtoupper($wallet->currency) === 'ETH') {
                    $txs = $this->ethService->getTransactionHistory($address, $limitPerWallet, $scanWindowFrom);

                    foreach ($txs as $tx) {
                                            // Normalize hash early for logging
                                            $txHash = $tx['hash'] ?? ($tx['transactionHash'] ?? null);

                                            if (!isset($tx['to'])) {
                                                Log::debug('Skipping tx without recipient', ['tx' => $txHash, 'wallet_id' => $wallet->id]);
                                                $skipped++;
                                                continue;
                                            }

                                            if (strtolower($tx['to']) !== strtolower($address)) {
                                                Log::debug('Skipping tx not addressed to this wallet', ['tx' => $txHash, 'to' => $tx['to'], 'wallet_address' => $address, 'wallet_id' => $wallet->id]);
                                                $skipped++;
                                                continue;
                                            }

                                            if (!$txHash) {
                                                Log::debug('Skipping tx with no hash', ['wallet_id' => $wallet->id, 'to' => $tx['to']]);
                            $skipped++;
                                                continue;
                                            }

                                            $currency = 'ETH';

                                            $blockNumber = isset($tx['blockNumber']) ? (int)$tx['blockNumber'] : null;
                                            $amount = isset($tx['value']) ? $tx['value'] : 0;

                                            $confirmations = 0;
                                            if ($currentBlock !== null && $blockNumber !== null) {
                                                $confirmations = max(0, $currentBlock - $blockNumber + 1);
                                            }

                                            // Log transaction found
                                            Log::debug('Blockchain transaction found', [
                                                'wallet_id' => $wallet->id,
                                                'tx_hash' => $txHash,
                                                'amount' => $amount,
                                            ]);

                                            // Duplicate check based on tx_hash + currency
                                            $existingDeposit = Deposit::where('tx_hash', $txHash)->where('currency', $currency)->first();
                                            if ($existingDeposit) {
                                                Log::debug('Duplicate deposit ignored for tx ' . $txHash . ' (wallet ' . $wallet->id . ')');
                                                $skipped++;

                                                // Update confirmations/status if needed
                                                if ($currentBlock !== null && $blockNumber !== null) {
                                                    $newConfirmations = max(0, $currentBlock - $blockNumber + 1);
                                                    $newStatus = $newConfirmations >= $this->confirmationThreshold ? 'confirmed' : 'pending';

                                                    if ($existingDeposit->confirmations !== $newConfirmations || $existingDeposit->status !== $newStatus) {
                                                        DB::transaction(function () use ($existingDeposit, $newConfirmations, $newStatus, $wallet) {
                                                            $existingDeposit->confirmations = $newConfirmations;
                                                            $existingDeposit->status = $newStatus;
                                                            $existingDeposit->save();
  
                                                            if ($newStatus === 'confirmed') {
                                                                \App\Models\Transaction::where(function ($query) use ($existingDeposit) {
                                                                        $query->where('tx_hash', $existingDeposit->tx_hash)
                                                                            ->orWhere('reference', $existingDeposit->tx_hash);
                                                                    })
                                                                    ->where('type', 'deposit')
                                                                    ->whereIn('status', ['pending', 'processing'])
                                                                    ->update([
                                                                        'status' => 'confirmed',
                                                                        'tx_hash' => $existingDeposit->tx_hash,
                                                                        'block_number' => $existingDeposit->block_number,
                                                                        'confirmations' => $newConfirmations,
                                                                        'receiver_wallet_address' => $wallet->wallet_address,
                                                                    ]);
                                                            }
                                                        });
                                                    }
  
                                                    if ($existingDeposit->status === 'confirmed') {
                                                        \App\Models\Transaction::where(function ($query) use ($existingDeposit) {
                                                                $query->where('tx_hash', $existingDeposit->tx_hash)
                                                                    ->orWhere('reference', $existingDeposit->tx_hash);
                                                            })
                                                            ->where('type', 'deposit')
                                                            ->whereIn('status', ['pending', 'processing'])
                                                            ->update([
                                                                'status' => 'confirmed',
                                                                'tx_hash' => $existingDeposit->tx_hash,
                                                                'block_number' => $existingDeposit->block_number,
                                                                'confirmations' => $existingDeposit->confirmations ?? 0,
                                                                'receiver_wallet_address' => $wallet->wallet_address,
                                                            ]);
                                                    }
 
                                                    // If it's now confirmed and unprocessed, process it
                                                    if ($existingDeposit->status === 'confirmed' && $existingDeposit->processed_at === null) {
                                                        $this->processDeposit($existingDeposit);
                                                    }
                                                }

                                                continue;
                                            }

                                            // Owner mapping
                                            $userId = null;
                                            $merchantId = null;
                                            if (!empty($wallet->owner_type) && !empty($wallet->owner_id)) {
                                                if (strtolower($wallet->owner_type) === 'user') {
                                                    $userId = $wallet->owner_id;
                                                } elseif (strtolower($wallet->owner_type) === 'merchant') {
                                                    $merchantId = $wallet->owner_id;
                                                }
                                            } else {
                                                if (!empty($wallet->user_id)) {
                                                    $userId = $wallet->user_id;
                                                }
                                            }

                                            $confirmations = (int)$confirmations;
                                            $senderWalletAddress = isset($tx['from']) ? strtolower($tx['from']) : null;

                                            // Create deposit as pending until receipt validation confirms the transaction succeeded.
                                            try {
                                                DB::transaction(function () use (&$created, $wallet, $userId, $merchantId, $amount, $txHash, $blockNumber, $confirmations, $currency, $senderWalletAddress) {
                                                    $deposit = Deposit::create([
                                                        'wallet_id' => $wallet->id,
                                                        'user_id' => $userId,
                                                        'merchant_id' => $merchantId,
                                                        'currency' => $currency,
                                                        'amount' => $amount,
                                                        'tx_hash' => $txHash,
                                                        'sender_wallet_address' => $senderWalletAddress,
 
                                                        'block_number' => $blockNumber,
                                                        'status' => 'pending',
                                                        'confirmations' => $confirmations ?? 0
                                                    ]);
 
                                                    // Invalidate cache so subsequent API calls will refresh
                                                    $this->balanceService->invalidateCache($wallet->id);
 
                                                    // Create internal transaction record for ledger/audit
                                                    \App\Models\Transaction::create([
                                                        'wallet_id' => $wallet->id,
                                                        'sender_id' => null,
                                                        'recipient_id' => $userId ?? $merchantId ?? null,
                                                        'sender_wallet_address' => $senderWalletAddress,
                                                        'receiver_wallet_address' => $wallet->wallet_address,
                                                        'type' => 'deposit',
                                                        'amount' => $amount,
                                                        'currency' => $currency,
                                                        'status' => 'pending',
                                                        'reference' => $deposit->tx_hash,
                                                        'tx_hash' => $deposit->tx_hash,
                                                        'block_number' => $deposit->block_number,
                                                        'confirmations' => $deposit->confirmations ?? 0,
                                                        'description' => 'On-chain deposit ' . ($deposit->tx_hash ?? '')
                                                    ]);

                                                    $created++;

                                                    Log::info('Deposit detected: ' . $deposit->id . ' tx=' . $txHash . ' wallet=' . $wallet->id . ' amount=' . $amount);

                                                    Log::info('Deposit detected', [
                                                        'tx' => $txHash,
                                                        'wallet' => $wallet->id,
                                                        'amount' => $amount,
                                                    ]);
                                                });
                                            } catch (\Throwable $e) {
                                                Log::error('Failed to persist deposit/transaction for tx ' . $txHash . ': ' . $e->getMessage());
                                                $errors[] = 'Failed to persist deposit for wallet ' . $wallet->id . ': ' . $e->getMessage();
                                            }
                                        }

                    // After scanning this wallet, update last_scanned_block to currentBlock if available
                    if (!$targetedHistoricalScan && $currentBlock !== null) {
                        try {
                            $wallet->last_scanned_block = $currentBlock;
                            $wallet->save();
                        } catch (\Throwable $e) {
                            Log::error('Failed to update last_scanned_block for wallet ' . $wallet->id . ': ' . $e->getMessage());
                        }
                    }
                } elseif (strtoupper($wallet->currency) === 'USDT') {
                    $contract = env('USDT_CONTRACT_ADDRESS');
                    if (empty($contract)) {
                        $skipped++;
                        continue;
                    }

                    $scanStart = $scanWindowFrom ?? 0;
                    $scanEnd = $targetedHistoricalScan ? $scanWindowTo : ($currentBlock ?? $scanStart);
                    $chunkSize = 2000;

                    for ($chunkStart = $scanStart; $chunkStart <= $scanEnd; $chunkStart += $chunkSize) {
                        $chunkEnd = min($chunkStart + $chunkSize - 1, $scanEnd);

                        try {
                            $transfers = $this->ethService->getTokenTransfersInRange($contract, $address, $chunkStart, $chunkEnd);
                        } catch (\Throwable $e) {
                            Log::error('USDT transfer chunk scan failed for wallet ' . $wallet->id . ' from ' . $chunkStart . ' to ' . $chunkEnd . ': ' . $e->getMessage());
                            break;
                        }

                        $chunkSucceeded = true;

                        foreach ($transfers as $tx) {
                            // Normalize hash early for logging
                            $txHash = $tx['hash'] ?? ($tx['transactionHash'] ?? null);

                            if (!isset($tx['to'])) {
                                Log::debug('Skipping token transfer without recipient', ['tx' => $txHash, 'wallet_id' => $wallet->id]);
                                $skipped++;
                                continue;
                            }

                            if (strtolower($tx['to']) !== strtolower($address)) {
                                Log::debug('Skipping token transfer not addressed to this wallet', ['tx' => $txHash, 'to' => $tx['to'], 'wallet_address' => $address, 'wallet_id' => $wallet->id]);
                                $skipped++;
                                continue;
                            }

                            if (!$txHash) {
                                Log::debug('Skipping token transfer with no hash', ['wallet_id' => $wallet->id, 'to' => $tx['to']]);
                                $skipped++;
                                continue;
                            }

                            $currency = 'USDT';

                            $blockNumber = isset($tx['blockNumber']) ? (int)$tx['blockNumber'] : null;
                            $amount = isset($tx['value']) ? $tx['value'] : 0;

                            $confirmations = 0;
                            if ($currentBlock !== null && $blockNumber !== null) {
                                $confirmations = max(0, $currentBlock - $blockNumber + 1);
                            }

                            // Log transaction found
                            Log::debug('Blockchain transaction found', [
                                'wallet_id' => $wallet->id,
                                'tx_hash' => $txHash,
                                'amount' => $amount,
                            ]);

                            // Duplicate check based on tx_hash + currency
                            $exists = Deposit::where('tx_hash', $txHash)->where('currency', $currency)->exists();
                            if ($exists) {
                                Log::info('Duplicate deposit ignored for tx ' . $txHash . ' (wallet ' . $wallet->id . ')');
                                $skipped++;

                                // Load existing deposit to update confirmations/status if needed.
                                // Receipt validation remains authoritative; do not mark a deposit confirmed
                                // from block depth alone.
                                $existingDeposit = Deposit::where('tx_hash', $txHash)->where('currency', $currency)->first();
                                if ($existingDeposit && $currentBlock !== null && $blockNumber !== null) {
                                    $newConfirmations = max(0, $currentBlock - $blockNumber + 1);
                                    $existingDeposit->confirmations = $newConfirmations;
                                    $existingDeposit->status = 'pending';
                                    $existingDeposit->save();
                                }

                                continue;
                            }

                            // Owner mapping
                            $userId = null;
                            $merchantId = null;
                            if (!empty($wallet->owner_type) && !empty($wallet->owner_id)) {
                                if (strtolower($wallet->owner_type) === 'user') {
                                    $userId = $wallet->owner_id;
                                } elseif (strtolower($wallet->owner_type) === 'merchant') {
                                    $merchantId = $wallet->owner_id;
                                }
                            } else {
                                if (!empty($wallet->user_id)) {
                                    $userId = $wallet->user_id;
                                }
                            }

                            $confirmations = (int)$confirmations;
                            $senderWalletAddress = isset($tx['from']) ? strtolower($tx['from']) : null;

                            try {
                                DB::transaction(function () use (&$created, $wallet, $userId, $merchantId, $amount, $txHash, $blockNumber, $confirmations, $currency, $senderWalletAddress) {
                                    $deposit = Deposit::create([
                                        'wallet_id' => $wallet->id,
                                        'user_id' => $userId,
                                        'merchant_id' => $merchantId,
                                        'currency' => $currency,
                                        'amount' => $amount,
                                        'tx_hash' => $txHash,
                                        'sender_wallet_address' => $senderWalletAddress,
                                        'block_number' => $blockNumber,
                                        'status' => 'pending',
                                        'confirmations' => $confirmations ?? 0
                                    ]);
 
                                    $this->balanceService->invalidateCache($wallet->id);
 
                                    \App\Models\Transaction::create([
                                        'wallet_id' => $wallet->id,
                                        'sender_id' => null,
                                        'recipient_id' => $userId ?? $merchantId ?? null,
                                        'sender_wallet_address' => $senderWalletAddress,
                                        'receiver_wallet_address' => $wallet->wallet_address,
                                        'type' => 'deposit',
                                        'amount' => $amount,
                                        'currency' => $currency,
                                        'status' => 'pending',
                                        'reference' => $deposit->tx_hash,
                                        'tx_hash' => $deposit->tx_hash,
                                        'block_number' => $deposit->block_number,
                                        'confirmations' => $deposit->confirmations ?? 0,
                                        'description' => 'On-chain deposit ' . ($deposit->tx_hash ?? '')
                                    ]);

                                    $created++;

                                    Log::info('Deposit detected: ' . $deposit->id . ' tx=' . $txHash . ' wallet=' . $wallet->id . ' amount=' . $amount);
                                });
                            } catch (\Throwable $e) {
                                Log::error('Failed to persist deposit/transaction for tx ' . $txHash . ': ' . $e->getMessage(), [
                                    'tx_hash' => $txHash,
                                    'wallet_id' => $wallet->id,
                                    'trace' => $e->getTraceAsString(),
                                ]);
                                $errors[] = 'Failed to persist deposit for wallet ' . $wallet->id . ' tx=' . $txHash . ': ' . $e->getMessage();
                                $chunkSucceeded = false;
                            }

                            if (!$chunkSucceeded) {
                                // Stop processing this chunk and do not advance last_scanned_block
                                break;
                            }
                        }

                        if (!$chunkSucceeded) {
                            // Do not advance last_scanned_block if chunk processing failed
                            break;
                        }

                        if (!$targetedHistoricalScan) {
                            try {
                                $wallet->last_scanned_block = $chunkEnd;
                                $wallet->save();
                            } catch (\Throwable $e) {
                                Log::error('Failed to update last_scanned_block for wallet ' . $wallet->id . ': ' . $e->getMessage());
                                break;
                            }
                        }
                    }

                    if (!$targetedHistoricalScan && $currentBlock !== null) {
                        try {
                            $wallet->last_scanned_block = $currentBlock;
                            $wallet->save();
                        } catch (\Throwable $e) {
                            Log::error('Failed to update last_scanned_block for wallet ' . $wallet->id . ': ' . $e->getMessage());
                        }
                    }
                } else {
                    continue;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Wallet ' . ($wallet->id ?? '?') . ' error: ' . $e->getMessage();
                Log::error('BlockchainDepositService scan error for wallet ' . ($wallet->id ?? '?') . ': ' . $e->getMessage());
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    public function processPendingConfirmedDeposits(): array
    {
        $processed = 0;
        $skipped = 0;
        $errors = [];

        $deposits = Deposit::where('status', 'confirmed')
            ->whereNull('processed_at')
            ->get();

        foreach ($deposits as $deposit) {
            try {
                if ($this->processDeposit($deposit)) {
                    $processed++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Deposit ' . ($deposit->id ?? '?') . ' error: ' . $e->getMessage();
                Log::error('BlockchainDepositService process error for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
            }
        }

        return ['processed' => $processed, 'skipped' => $skipped, 'errors' => $errors];
    }

    protected function processDeposit(Deposit $deposit): bool
    {
        if ($deposit->status !== 'confirmed' || $deposit->processed_at !== null) {
            return false;
        }

        return DB::transaction(function () use ($deposit) {
            $deposit = Deposit::lockForUpdate()->find($deposit->id);
            if (!$deposit || $deposit->status !== 'confirmed' || $deposit->processed_at !== null) {
                return false;
            }

            $wallet = Wallet::lockForUpdate()->find($deposit->wallet_id);
            if (!$wallet) {
                return false;
            }

            // Mark deposit as processed
            $deposit->processed_at = now();
            $deposit->save();

            // Only BalanceSyncService is allowed to write wallet balances.
            $syncResult = $this->balanceService->syncWallet($wallet);
  
            // Ensure related transaction is marked completed when deposit is processed
            \App\Models\Transaction::where('reference', $deposit->tx_hash)
                ->where('type', 'deposit')
                ->where('status', 'pending')
                ->update(['status' => 'confirmed']);
  
            Log::info('Processed deposit ' . $deposit->id . ' and synced wallet ' . $wallet->id . ' balance to ' . ($syncResult['balance'] ?? $wallet->balance));
 
                        // Create a user notification about the deposit so it appears in the bell dropdown
                        try {
                            $ownerId = $deposit->user_id ?? $deposit->merchant_id ?? $wallet->user_id ?? null;
                            if ($ownerId) {
                                $title = __('notifications.deposit_received.title');
                                $formattedAmount = rtrim(rtrim((string)$deposit->amount, '0'), '.');
                                if ($formattedAmount === '') {
                                    $formattedAmount = '0';
                                }
                                $senderAddress = $deposit->sender_wallet_address ? $deposit->sender_wallet_address : __('notifications.unknown_sender');
                                $message = __('notifications.deposit_received.message', [
                                    'amount' => $formattedAmount,
                                    'currency' => $deposit->currency,
                                    'sender' => $senderAddress,
                                ]);
                                Notification::createNotification($ownerId, $title, $message, 'success', 'fa-money-bill-wave');
                            }
                        } catch (\Throwable $e) {
                            Log::error('Failed to create notification for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
                        }

                        return true;
        });
    }
}

