<?php
// Bootstrap Laravel and run UpdateDepositConfirmationsJob::dispatchSync(), then print TX 193 before/after
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Jobs\UpdateDepositConfirmationsJob;

$tx = Transaction::find(193);
if (!$tx) {
    echo "Transaction 193 not found\n";
    exit(1);
}

$before = [
    'status' => $tx->status,
    'tx_hash' => $tx->tx_hash,
    'block_number' => $tx->block_number,
    'confirmations' => $tx->confirmations,
];

echo "BEFORE: " . json_encode($before, JSON_UNESCAPED_SLASHES) . "\n";

// Dispatch the job synchronously
try {
    UpdateDepositConfirmationsJob::dispatchSync();
} catch (Throwable $e) {
    echo "Job threw: " . $e->getMessage() . "\n";
}

$tx->refresh();
$after = [
    'status' => $tx->status,
    'tx_hash' => $tx->tx_hash,
    'block_number' => $tx->block_number,
    'confirmations' => $tx->confirmations,
];

echo "AFTER: " . json_encode($after, JSON_UNESCAPED_SLASHES) . "\n";
