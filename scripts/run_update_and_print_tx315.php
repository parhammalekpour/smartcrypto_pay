<?php
// Bootstrap Laravel and run UpdateDepositConfirmationsJob::dispatchSync(), then print TX 315 before/after
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Jobs\UpdateDepositConfirmationsJob;

$tx = Transaction::find(315);
if (!$tx) {
    echo "Transaction 315 not found\n";
    exit(1);
}

$before = [
    'id' => $tx->id,
    'status' => $tx->status,
    'tx_hash' => $tx->tx_hash,
    'block_number' => $tx->block_number,
    'block_hash' => $tx->block_hash,
    'transaction_index' => $tx->transaction_index,
    'confirmations' => $tx->confirmations,
    'receipt_status' => $tx->receipt_status,
    'confirmed_at' => $tx->confirmed_at,
    'last_checked_at' => $tx->last_checked_at ?? null,
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
    'id' => $tx->id,
    'status' => $tx->status,
    'tx_hash' => $tx->tx_hash,
    'block_number' => $tx->block_number,
    'block_hash' => $tx->block_hash,
    'transaction_index' => $tx->transaction_index,
    'confirmations' => $tx->confirmations,
    'receipt_status' => $tx->receipt_status,
    'confirmed_at' => $tx->confirmed_at,
    'last_checked_at' => $tx->last_checked_at ?? null,
];

echo "AFTER: " . json_encode($after, JSON_UNESCAPED_SLASHES) . "\n";
