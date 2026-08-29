<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\EthereumService;
use App\Services\WalletBalanceUpdated as WalletBalanceUpdatedEvent;

class BalanceSyncService
{
    protected EthereumService $ethereumService;

    public function __construct(?EthereumService $ethereumService = null)
    {
        $this->ethereumService = $ethereumService ?? new EthereumService();
    }

    /**
     * Outgoing withdrawals remain reserved/spent until a terminal failure state is reached.
     * This includes already confirmed/completed sends because the site should not present the
     * funds as still available after they were dispatched from the wallet.
     */
    protected static function activeWithdrawalStatuses(): array
    {
       return ['processing', 'broadcasting', 'pending', 'confirmed', 'completed'];
    }

    /**
     * Calculate wallet balances and update the database.
     * Returns array: [ 'confirmed' => string, 'pending' => string, 'withdrawn' => string, 'balance' => string ]
     */
    protected function getOnChainBalance(Wallet $wallet): ?string
    {
        $currency = strtoupper($wallet->currency ?? 'ETH');
        $address = trim((string)$wallet->wallet_address);
        if ($address === '') {
            return null;
        }

        try {
            if ($currency === 'ETH') {
                $balance = $this->ethereumService->getBalance($address);
                return is_string($balance) ? $balance : null;
            }

            if ($currency === 'USDT' || $currency === 'USD') {
                $contractAddress = trim((string) env('USDT_CONTRACT_ADDRESS'));
                if ($contractAddress === '') {
                    return null;
                }

                $balance = $this->ethereumService->getTokenBalance($contractAddress, $address);
                return is_string($balance) ? $balance : null;
            }
        } catch (\Throwable $e) {
            Log::warning('BalanceSyncService: failed to fetch on-chain balance for wallet ' . $wallet->id . ' (' . $currency . '): ' . $e->getMessage());
            return null;
        }

        return null;
    }

    public function calculateWalletBalance(Wallet $wallet): array
    {
        $walletId = $wallet->id;

        $onChainBalance = $this->getOnChainBalance($wallet);
        if ($onChainBalance !== null) {
            // When on-chain balance is available use it as the authoritative confirmed balance,
            // but subtract all active outgoing reservations so the wallet never overstates spendable funds.
            $withdrawn = Transaction::where('wallet_id', $walletId)
                ->whereIn('type', ['withdraw', 'withdrawal'])
                ->whereIn('status', self::activeWithdrawalStatuses())
                ->sum('amount');

            $pendingDeposits = Deposit::where('wallet_id', $walletId)
                ->where('status', 'pending')
                ->sum('amount');

            // Ledger effects from internal transactions (exclude on-chain-derived transactions which have tx_hash set)
            $ledgerDebits = Transaction::where('wallet_id', $walletId)
                ->whereIn('type', ['transfer', 'payment'])
                ->where('status', 'completed')
                ->whereNull('tx_hash')
                ->sum('amount');

            $ledgerCredits = Transaction::where('wallet_id', $walletId)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->whereNull('tx_hash')
                ->sum('amount');

            $withdrawnStr = (string)($withdrawn ?? '0');
            $pendingStr = (string)($pendingDeposits ?? '0');
            $ledgerDebitsStr = (string)($ledgerDebits ?? '0');
            $ledgerCreditsStr = (string)($ledgerCredits ?? '0');

 // Choose decimal scale: USDT uses 6 decimals, ETH uses 18
            $currency = strtoupper($wallet->currency ?? 'ETH');
            $scale = ($currency === 'USDT') ? 6 : 18;

            // compute available balance = onChain - withdrawn + ledgerNet using BC math at appropriate scale
            if (function_exists('bcsub') && function_exists('bcadd')) {
                $available = bcsub((string)$onChainBalance, $withdrawnStr, $scale);
                $ledgerNet = bcsub($ledgerCreditsStr, $ledgerDebitsStr, $scale);
                $available = bcadd($available, $ledgerNet, $scale);
            } else {
                $available = (string)(((float)$onChainBalance) - ((float)$withdrawnStr) + ((float)$ledgerCreditsStr) - ((float)$ledgerDebitsStr));
            }

            return [
                'confirmed' => (string)$onChainBalance,
                'pending' => (string)$pendingStr,
                'withdrawn' => (string)$withdrawnStr,
                'balance' => (string)$available,
                'source' => 'chain',
            ];
        }

        $confirmed = Deposit::where('wallet_id', $walletId)
            ->where('status', 'confirmed')
            ->sum('amount');

        $pending = Deposit::where('wallet_id', $walletId)
            ->where('status', 'pending')
            ->sum('amount');

        // Active outgoing reservations remain deducted from spendable balance even when the
        // on-chain balance is unavailable; terminal failure states are intentionally excluded.
        $withdrawn = Transaction::where('wallet_id', $walletId)
            ->whereIn('type', ['withdraw', 'withdrawal'])
            ->whereIn('status', self::activeWithdrawalStatuses())
            ->sum('amount');

        // Ledger effects from internal transactions (exclude on-chain-derived transactions which have tx_hash set)
        $ledgerDebits = Transaction::where('wallet_id', $walletId)
            ->whereIn('type', ['transfer', 'payment'])
            ->where('status', 'completed')
            ->whereNull('tx_hash')
            ->sum('amount');

        $ledgerCredits = Transaction::where('wallet_id', $walletId)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->whereNull('tx_hash')
            ->sum('amount');

        // Ensure string math using bc* functions when available
        $confirmedStr = (string)($confirmed ?? '0');
        $pendingStr = (string)($pending ?? '0');
        $withdrawnStr = (string)($withdrawn ?? '0');
        $ledgerDebitsStr = (string)($ledgerDebits ?? '0');
        $ledgerCreditsStr = (string)($ledgerCredits ?? '0');

        // Choose decimal scale: USDT uses 6 decimals, ETH uses 18
        $currency = strtoupper($wallet->currency ?? 'ETH');
        $scale = ($currency === 'USDT') ? 6 : 18;

        // Define balance as confirmed + pending - withdrawn + ledgerNet so pending deposits are reflected and internal ledger moves are applied
        if (function_exists('bcadd') && function_exists('bcsub')) {
            $sumDeposits = bcadd($confirmedStr, $pendingStr, $scale);
            $balancePreLedger = bcsub($sumDeposits, $withdrawnStr, $scale);
            $ledgerNet = bcsub($ledgerCreditsStr, $ledgerDebitsStr, $scale);
            $balance = bcadd($balancePreLedger, $ledgerNet, $scale);
        } else {
            $balance = (string)(((float)$confirmedStr + (float)$pendingStr) - ((float)$withdrawnStr) + ((float)$ledgerCreditsStr) - ((float)$ledgerDebitsStr));
        }

        return [
            'confirmed' => (string)$confirmedStr,
            'pending' => (string)$pendingStr,
            'withdrawn' => (string)$withdrawnStr,
            'balance' => (string)$balance,
        ];
    }

    /**
     * Recalculate and persist wallet balance. Broadcasts and caches updates.
     */
    public function syncWallet(Wallet $wallet): array
    {
        return Cache::lock($this->cacheKey($wallet->id) . ':sync', 30)->block(10, function () use ($wallet) {
                return DB::transaction(function () use ($wallet) {
            // Re-fetch the wallet under lock to ensure we never operate on a stale instance
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            $results = $this->calculateWalletBalance($wallet);

            // Persist: onchain confirmed balance and the computed available balance
            $oldOnchain = (string)($wallet->onchain_balance ?? '0');
            $oldAvailable = (string)($wallet->balance ?? '0');

            $confirmedBalance = (string)($results['confirmed'] ?? '0');
            $availableBalance = (string)($results['balance'] ?? '0');

            Log::error('SYNC DEBUG BEFORE SAVE', [
                'wallet_id' => $wallet->id,
                'results_confirmed' => $results['confirmed'] ?? null,
                'confirmedBalance_variable' => $confirmedBalance,
                'results_balance' => $results['balance'] ?? null,
                'availableBalance_variable' => $availableBalance,
                'db_before_onchain' => $wallet->onchain_balance,
            ]);

            $wallet->onchain_balance = $confirmedBalance;
            $wallet->balance = $availableBalance;

            \Log::info('WALLET BEFORE SAVE', [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'onchain_balance' => $wallet->onchain_balance,
                'dirty' => $wallet->getDirty(),
            ]);

            $wallet->saveQuietly();

            // expose variables used later for logging/broadcast
            $newOnchain = $confirmedBalance;
            $newAvailable = $availableBalance;

            // Invalidate and set cache (cache continues to contain both confirmed and available values)
            $cacheKey = $this->cacheKey($wallet->id);
            Cache::put($cacheKey, $results, 10); // 10 seconds

            // Broadcast event if the canonical confirmed on-chain balance changed or available changed
            if ($oldOnchain !== $newOnchain || $oldAvailable !== $newAvailable) {
                Log::info('Balance updated for wallet ' . $wallet->id . ': onchain ' . $oldOnchain . ' -> ' . $newOnchain . ', available ' . $oldAvailable . ' -> ' . $newAvailable);
                broadcast(new WalletBalanceUpdatedEvent($wallet, $results));
            } else {
                Log::info('Balance sync for wallet ' . $wallet->id . ' - no change');
            }

            return array_merge($results, ['wallet_id' => $wallet->id]);
        });
        });
    }

    public function verifyConsistency(Wallet $wallet): bool
    {
        $computed = $this->calculateWalletBalance($wallet);
        $currentOnchain = (string)($wallet->onchain_balance ?? '0');
        // The wallet->onchain_balance stores the confirmed on-chain balance. Compare against computed confirmed.
        $consistent = ($currentOnchain === ($computed['confirmed'] ?? '0'));
        if (!$consistent) {
            Log::warning('Wallet balance inconsistency detected for wallet ' . $wallet->id . ': db_onchain=' . $currentOnchain . ' computed_confirmed=' . ($computed['confirmed'] ?? '0'));
        }
        return $consistent;
    }

    public function cacheKey($walletId): string
    {
        return 'wallet_balance:' . $walletId;
    }

    public function invalidateCache($walletId)
    {
        Cache::forget($this->cacheKey($walletId));
    }
}
