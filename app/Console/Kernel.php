<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\BlockchainScanJob;
use App\Jobs\UpdateDepositConfirmationsJob;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\BlockchainScan::class,
        \App\Console\Commands\BlockchainProcessDeposits::class,
        \App\Console\Commands\WalletSyncBalances::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Blockchains are polled on a one-minute interval and dispatched to the blockchain queue.
        $schedule->job(new BlockchainScanJob(50), 'blockchain')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('blockchain.scan');

        $schedule->job(new UpdateDepositConfirmationsJob(), 'blockchain')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('blockchain.confirmations');

        $schedule->command('blockchain:process-deposits')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('blockchain.process-deposits');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
