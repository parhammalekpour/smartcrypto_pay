<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Services\BalanceSyncService;
use Illuminate\Support\Facades\Log;

class WalletSyncBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:sync-balances {--wallet_id= : Optional specific wallet id to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and persist balances for all wallets (or a single wallet) using deposits and withdrawals';

    protected BalanceSyncService $balanceService;

    public function __construct(BalanceSyncService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    public function handle()
    {
        $walletId = $this->option('wallet_id');

        if ($walletId) {
            $wallet = Wallet::find($walletId);
            if (!$wallet) {
                $this->error('Wallet not found: ' . $walletId);
                return 1;
            }

            $this->info('Syncing wallet ' . $wallet->id);
            try {
                $this->balanceService->syncWallet($wallet);
                $this->info('Synced wallet ' . $wallet->id);
            } catch (\Throwable $e) {
                $this->error('Error syncing wallet ' . $wallet->id . ': ' . $e->getMessage());
                Log::error('Error in wallet:sync-balances for wallet ' . $wallet->id . ': ' . $e->getMessage());
                return 1;
            }

            return 0;
        }

        $this->info('Syncing all wallets...');
        $bar = $this->output->createProgressBar(Wallet::count());
        $bar->start();

        Wallet::chunk(100, function ($wallets) use ($bar) {
            foreach ($wallets as $wallet) {
                try {
                    $this->balanceService->syncWallet($wallet);
                } catch (\Throwable $e) {
                    Log::error('Error syncing wallet ' . $wallet->id . ': ' . $e->getMessage());
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Done.');

        return 0;
    }
}
