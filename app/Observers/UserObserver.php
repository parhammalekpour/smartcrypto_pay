<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $currencies = ['BTC', 'ETH', 'USDT'];

        DB::transaction(function () use ($user, $currencies) {
            foreach ($currencies as $currency) {
                // Create a wallet record and let the Wallet model's creating() hook
                // generate a real HD wallet (address + encrypted private key) via
                // BlockchainWalletService. Do not use placeholder or fake addresses.
                $user->wallets()->create([
                    'currency' => $currency,
                    'balance' => 0,
                ]);
            }
        });
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
