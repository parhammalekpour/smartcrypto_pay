<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateDepositConfirmationsJob;
use App\Services\EthereumService;
use App\Services\BalanceSyncService;
use App\Models\Transaction;

class ConfirmSingleTx extends Command
{
    protected $signature = 'confirm:tx {txhash} {--interval=10} {--once}';

    protected $description = 'Continuously run confirmation job focused on a single tx_hash for development/debug (non-destructive)';

    public function handle(): int
    {
        $txHash = (string) $this->argument('txhash');
        $interval = (int) $this->option('interval');
        $once = (bool) $this->option('once');

        if ($txHash === '') {
            $this->error('txhash is required');
            return 1;
        }

        $lockFilePath = storage_path('locks/confirm_tx_' . preg_replace('/[^a-z0-9]/i', '_', $txHash) . '.lock');
        @mkdir(dirname($lockFilePath), 0777, true);

        $fp = fopen($lockFilePath, 'c');
        if ($fp === false) {
            $this->error('Unable to open lock file: ' . $lockFilePath);
            return 2;
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            $this->error('Another instance appears to be running; exiting');
            return 0;
        }

        $this->info('Starting confirm:tx for ' . $txHash . ' interval=' . $interval . 's');

        try {
            do {
                $start = now();

                $tx = Transaction::where('tx_hash', $txHash)->orWhere('reference', $txHash)->first();
                if (!$tx) {
                    $this->info('Transaction not found: ' . $txHash);
                    Log::warning('ConfirmSingleTx: transaction not found', ['tx_hash' => $txHash]);
                    if ($once) break;
                    sleep(max(1, $interval));
                    continue;
                }

                $broadcastAt = $tx->broadcasted_at ?? $tx->created_at;
                $this->info('Broadcast => ' . ($broadcastAt ? $broadcastAt->toDateTimeString() : 'null'));

                // Dispatch timestamp (when this loop attempts confirmation)
                $dispatchAt = now();
                $this->info('Dispatch => ' . $dispatchAt->toDateTimeString());
                Log::info('ConfirmSingleTx dispatch', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'dispatch_at' => $dispatchAt->toDateTimeString()]);

                // RPC Receipt check (explicit, to capture RPC timestamp)
                try {
                    $eth = app(EthereumService::class);
                    $rpcStart = now();
                    $this->info('RPC Receipt start => ' . $rpcStart->toDateTimeString());
                    Log::info('ConfirmSingleTx rpc_start', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'rpc_start' => $rpcStart->toDateTimeString()]);

                    $receiptResult = $eth->getTransactionReceipt($txHash);

                    $rpcEnd = now();
                    $this->info('RPC Receipt end => ' . $rpcEnd->toDateTimeString());
                    Log::info('ConfirmSingleTx rpc_end', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'rpc_end' => $rpcEnd->toDateTimeString()]);

                    $this->line('RPC preview: ' . json_encode(array_intersect_key($receiptResult, array_flip(['receipt','confirmations'])), JSON_UNESCAPED_SLASHES));
                } catch (\Throwable $e) {
                    $this->error('RPC receipt call failed: ' . $e->getMessage());
                    Log::warning('ConfirmSingleTx rpc failed', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'error' => $e->getMessage()]);
                }

                // Job Start
                $jobStart = now();
                $this->info('Job Start => ' . $jobStart->toDateTimeString());
                Log::info('ConfirmSingleTx job_start', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'job_start' => $jobStart->toDateTimeString()]);

                try {
                    // Run the confirmation job synchronously (safe for development)
                    $job = new UpdateDepositConfirmationsJob();
                    // call handle using the container to resolve dependencies
                    app()->call([$job, 'handle']);
                } catch (\Throwable $e) {
                    $this->error('Job execution failed: ' . $e->getMessage());
                    Log::error('ConfirmSingleTx job execution failed', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'error' => $e->getMessage()]);
                }

                $jobEnd = now();
                $this->info('Job End => ' . $jobEnd->toDateTimeString());
                Log::info('ConfirmSingleTx job_end', ['tx_id' => $tx->id, 'tx_hash' => $txHash, 'job_end' => $jobEnd->toDateTimeString()]);

                // Refresh and print DB state
                $tx->refresh();
                $this->info('DB Update => ' . now()->toDateTimeString());
                $this->line('TX DB: ' . json_encode([
                    'id' => $tx->id,
                    'status' => $tx->status,
                    'tx_hash' => $tx->tx_hash,
                    'block_number' => $tx->block_number,
                    'block_hash' => $tx->block_hash,
                    'transaction_index' => $tx->transaction_index,
                    'confirmations' => $tx->confirmations,
                    'receipt_status' => $tx->receipt_status,
                    'confirmed_at' => $tx->confirmed_at,
                ], JSON_UNESCAPED_SLASHES));

                if ($once) break;

                $elapsed = now()->diffInSeconds($start);
                $sleepFor = max(1, $interval - $elapsed);
                sleep($sleepFor);
            } while (true);
        } finally {
            // release lock
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return 0;
    }
}
