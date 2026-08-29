<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind EthereumService as a singleton so the in-process RPC cache is reused
        $this->app->singleton(\App\Services\EthereumService::class, function ($app) {
            return new \App\Services\EthereumService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        // Observe deposits to automatically sync balances when confirmed
        \App\Models\Deposit::observe(\App\Observers\DepositObserver::class);
    }
}
