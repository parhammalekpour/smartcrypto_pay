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
     * Calculate wallet balances and update the database.
     * Returns array: [ 'confirmed' => string, 'pending' => string, 'withdrawn' => string, 'balance' => string ]
     */
    protected function getOnChainBalance(Wallet $wallet): ?string
    {
        if (strtoupper($wallet->currency ?? '') !== 'ETH') {
            return null;
        }

        $address = trim((string)$wallet->wallet_address);
        if ($address === '') {
            return null;
        }

        try {
            $balance = $this->ethereumService->getBalance($address);
            return is_string($balance) ? $balance : null;
        } catch (\Throwable $e) {
            Log::warning('BalanceSyncService: failed to fetch on-chain balance for wallet ' . $wallet->id . ': ' . $e->getMessage());
            return null;
        }
    }

    public function calculateWalletBalance(Wallet $wallet): array
    {
        $walletId = $wallet->id;

        $onChainBalance = $this->getOnChainBalance($wallet);
        if ($onChainBalance !== null) {
            return [
                'confirmed' => $onChainBalance,
                'pending' => '0',
                'withdrawn' => '0',
                'balance' => $onChainBalance,
                'source' => 'chain',
            ];
        }

        $confirmed = Deposit::where('wallet_id', $walletId)
            ->where('status', 'confirmed')
            ->sum('amount');

        $pending = Deposit::where('wallet_id', $walletId)
            ->where('status', 'pending')
            ->sum('amount');

        // Treat withdrawals as Transaction entries with type 'withdraw' or 'withdrawal'.
        // Include pending and completed withdrawals in the withdrawn sum so that
        // once a withdrawal is broadcast (status pending) the wallet balance
        // reflects the spent amount immediately.
        $withdrawn = Transaction::where('wallet_id', $walletId)
            ->whereIn('type', ['withdraw', 'withdrawal'])
            ->whereIn('status', ['pending', 'completed', 'confirmed'])
            ->sum('amount');

        // Ensure string math using bc* functions when available
        $confirmedStr = (string)($confirmed ?? '0');
        $pendingStr = (string)($pending ?? '0');
        $withdrawnStr = (string)($withdrawn ?? '0');

        // Define balance as confirmed + pending - withdrawn so pending deposits are reflected in wallet balance
        if (function_exists('bcadd') && function_exists('bcsub')) {
            $sumDeposits = bcadd($confirmedStr, $pendingStr, 18);
            $balance = bcsub($sumDeposits, $withdrawnStr, 18);
        } else {
            $balance = (string)(((float)$confirmedStr + (float)$pendingStr) - ((float)$withdrawnStr));
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
        return DB::transaction(function () use ($wallet) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);
            if (!$wallet) {
                throw new \RuntimeException('Wallet not found for sync');
            }

            $results = $this->calculateWalletBalance($wallet);

            $oldBalance = (string)($wallet->balance ?? '0');
            $newBalance = $results['balance'];

            $wallet->balance = $newBalance;
            $wallet->save();

            // Invalidate and set cache
            $cacheKey = $this->cacheKey($wallet->id);
            Cache::put($cacheKey, $results, 10); // 10 seconds

            // Broadcast event if changed
            if ($oldBalance !== $newBalance) {
                Log::info('Balance updated for wallet ' . $wallet->id . ': ' . $oldBalance . ' -> ' . $newBalance);
                broadcast(new WalletBalanceUpdatedEvent($wallet, $results));
            } else {
                Log::info('Balance sync for wallet ' . $wallet->id . ' - no change');
            }

            return array_merge($results, ['wallet_id' => $wallet->id]);
        });
    }

    public function verifyConsistency(Wallet $wallet): bool
    {
        $computed = $this->calculateWalletBalance($wallet);
        $current = (string)($wallet->balance ?? '0');
        $consistent = ($current === $computed['balance']);
        if (!$consistent) {
            Log::warning('Wallet balance inconsistency detected for wallet ' . $wallet->id . ': db=' . $current . ' computed=' . $computed['balance']);
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
