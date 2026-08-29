<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\BlockchainScanJob;
use App\Jobs\UpdateDepositConfirmationsJob;
use App\Jobs\UpdateUSDTDepositConfirmationsJob;

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

        // Separate scheduled job for USDT-only confirmations (reuses EthereumService helpers)
        $schedule->job(new UpdateUSDTDepositConfirmationsJob(), 'blockchain')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('blockchain.confirmations-usdt');

        // Recover transactions that are stuck in broadcasting without a tx_hash (periodic, safe, read-only to chain)
        $schedule->job(new \App\Jobs\RecoverBroadcastingTransactions(), 'blockchain')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->name('blockchain.recover-broadcast');

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
