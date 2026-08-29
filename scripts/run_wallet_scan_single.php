<?php
// Run a single wallet scan by invoking BlockchainScanJob->handle synchronously.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\BlockchainScanJob;
use App\Services\BlockchainDepositService;

if ($argc < 2) {
    echo "Usage: php run_wallet_scan_single.php <walletId> [limitPerWallet]\n";
    exit(1);
}

$walletId = (int)$argv[1];
$limitPerWallet = isset($argv[2]) ? (int)$argv[2] : (int)config('ethereum.scan_wallets_per_job', 20);

echo "Starting scan for wallet $walletId (limitPerWallet={$limitPerWallet})\n";
try {
    $job = new BlockchainScanJob($limitPerWallet, $walletId);
    $scanner = app(BlockchainDepositService::class);
    $start = microtime(true);
    $summary = $scanner->scanOnce($limitPerWallet, $walletId);
    $dur = round(microtime(true) - $start, 3);
    echo "Completed wallet $walletId in {$dur}s; summary: " . json_encode($summary) . "\n";
    exit(0);
} catch (Throwable $e) {
    echo "Error scanning wallet $walletId: " . $e->getMessage() . "\n";
    exit(2);
}
