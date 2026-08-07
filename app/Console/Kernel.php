<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\BlockchainScan::class,
        \App\Console\Commands\BlockchainProcessDeposits::class,
        \App\Console\Commands\WalletSyncBalances::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Dispatch the blockchain scan as a queued job every minute
        $schedule->job(new \App\Jobs\BlockchainScanJob(50), 'blockchain')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        // Dispatch the confirmation updater job every minute (updates pending deposits confirmations)
        $schedule->job(new \App\Jobs\UpdateDepositConfirmationsJob(), 'blockchain')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        // Keep processing confirmed deposits (legacy command) as a fallback
        $schedule->command('blockchain:process-deposits')->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
