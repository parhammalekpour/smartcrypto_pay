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

    public function updated(Deposit $deposit)
    {
        // If deposit status became confirmed, trigger wallet balance sync
        if ($deposit->wasChanged('status') && $deposit->status === 'confirmed') {
            try {
                $wallet = $deposit->wallet;
                if ($wallet) {
                    Log::info('Deposit confirmed: ' . $deposit->id . ' triggering balance sync for wallet ' . $wallet->id);
                    $this->balanceService->syncWallet($wallet);
                }
            } catch (\Throwable $e) {
                Log::error('DepositObserver failed to sync balance for deposit ' . ($deposit->id ?? '?') . ': ' . $e->getMessage());
            }
        }
    }
}
