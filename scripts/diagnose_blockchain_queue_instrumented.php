<?php
// Diagnosing blockchain queue with instrumentation: DB query counts and EthereumService RPC stats
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Jobs\BlockchainScanJob;
use App\Jobs\UpdateDepositConfirmationsJob;
use App\Jobs\UpdateUSDTDepositConfirmationsJob;
use App\Jobs\RecoverBroadcastingTransactions;

function t() { return microtime(true); }

echo "Diagnosing blockchain queue (instrumented)\n";

// Basic counts
$walletCount = Wallet::count();
$pendingDeposits = Deposit::where('status','pending')->count();
$pendingWithdrawals = Transaction::whereIn('type',['withdraw','withdrawal'])->whereIn('status',['pending','processing','broadcasting'])->count();
$queuedJobs = DB::table(env('DB_QUEUE_TABLE','jobs'))->where('queue','blockchain')->count();

echo "Wallets: $walletCount\n";
echo "Pending deposits: $pendingDeposits\n";
echo "Pending withdrawals: $pendingWithdrawals\n";
echo "Queued jobs (queue=blockchain): $queuedJobs\n";

// List some queued jobs
$jobs = DB::table(env('DB_QUEUE_TABLE','jobs'))->where('queue','blockchain')->orderBy('id','asc')->limit(20)->get();
if ($jobs->isEmpty()) {
    echo "No queued jobs in blockchain queue.\n";
} else {
    echo "First queued jobs (id, attempts, available_at, payload class preview):\n";
    foreach ($jobs as $j) {
        $payload = json_decode($j->payload, true);
        $class = null;
        if (isset($payload['data']['command'])) {
            // serialized job
            $class = substr($payload['data']['command'], 0, 200);
        }
        echo "- id={$j->id} attempts={$j->attempts} available_at={$j->available_at} reserved_at={$j->reserved_at} class_preview={$class}\n";
    }
}

// Instrumentation: count DB queries and expose RPC stats per-job
$queryCount = 0;
DB::listen(function ($query) use (&$queryCount) {
    $queryCount++;
});

$ethService = app(\App\Services\EthereumService::class);

// Helper to time job execution safely
function runJobTimed($callable, $label) {
    global $queryCount, $ethService;
    echo "\nRunning: $label\n";
    $start = microtime(true);

    // reset query counter
    $queryCount = 0;

    try {
        $callable();
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
        echo "Exception in $label: " . $e->getMessage() . "\n";
    }
    $end = microtime(true);
    $dur = $end - $start;

    // rpc stats snapshot
    $rpcStats = method_exists($ethService, 'getRpcStats') ? $ethService->getRpcStats() : null;

    echo "$label duration: " . round($dur,3) . "s (success=" . ($ok? 'true':'false') . ")\n";
    echo "  DB queries during run: $queryCount\n";
    if ($rpcStats !== null) {
        echo "  RPC calls this process: " . ($rpcStats['rpcCalls'] ?? '?') . ", cache hits: " . ($rpcStats['rpcCacheHits'] ?? '?') . ", cache size: " . ($rpcStats['rpcCacheSize'] ?? '?') . "\n";
        if (!empty($rpcStats['rpcOps']) && is_array($rpcStats['rpcOps'])) {
            echo "  RPC ops breakdown:\n";
            foreach ($rpcStats['rpcOps'] as $op => $count) {
                echo "    - $op: $count\n";
            }
        }
    }
}

// Run main jobs synchronously but with safe parameters to avoid overload.
// BlockchainScanJob: run with limit=10 and singleWalletId=null (will scan wallets but limit tx per wallet)
runJobTimed(function() { (new BlockchainScanJob(10))->handle(app(App\Services\BlockchainDepositService::class)); }, 'BlockchainScanJob(limit=10)');

// UpdateDepositConfirmationsJob
runJobTimed(function() { (new UpdateDepositConfirmationsJob())->handle(app(App\Services\EthereumService::class), app(App\Services\BalanceSyncService::class)); }, 'UpdateDepositConfirmationsJob');

// Update USDT confirmations
runJobTimed(function() { (new UpdateUSDTDepositConfirmationsJob())->handle(app(App\Services\EthereumService::class), app(App\Services\BalanceSyncService::class)); }, 'UpdateUSDTDepositConfirmationsJob');

// Recover broadcasting transactions
runJobTimed(function() { (new RecoverBroadcastingTransactions(20))->handle(app(App\Services\EthereumService::class)); }, 'RecoverBroadcastingTransactions(limit=20)');

echo "\nDiagnosis complete.\n";
