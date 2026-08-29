<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Services\BalanceSyncService;
use Illuminate\Support\Facades\Log;

class DepositObserver
{
    protected BalanceSyncService $balanceService;

    public function __construct()
    {
        $this->balanceService = new BalanceSyncService();
    }

    public function created(Deposit $deposit)
    {
        $this->syncForWallet($deposit);
    }

    public function updated(Deposit $deposit)
    {
        if ($deposit->wasChanged('status')) {
            $this->syncForWallet($deposit);
        }
    }

    protected function syncForWallet(Deposit $deposit): void
    {
        try {
            $wallet = $deposit->wallet;
            if ($wallet) {
                $this->balanceService->syncWallet($wallet);
                Log::info('Deposit observer synced wallet balance', [
                    'deposit_id' => $deposit->id,
                    'wallet_id' => $wallet->id,
                    'status' => $deposit->status,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('DepositObserver failed to sync balance for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
        }
    }
}
