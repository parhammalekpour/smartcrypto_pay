<?php
// Bootstrap Laravel and print deposits + transactions for wallet id 143
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Deposit;
use App\Models\Transaction;

$walletId = 143;

echo "Deposits for wallet_id={$walletId}:\n";
$deposits = Deposit::where('wallet_id', $walletId)->orderBy('created_at', 'asc')->get();
if ($deposits->count() === 0) {
    echo "(none)\n";
} else {
    foreach ($deposits as $d) {
        echo implode('|', [
            $d->id,
            $d->currency,
            (string)$d->amount,
            $d->tx_hash,
            $d->status,
            $d->confirmations ?? 0,
            $d->created_at ?? ''
        ]) . PHP_EOL;
    }
}

echo "\nTransactions for wallet_id={$walletId}:\n";
$txs = Transaction::where('wallet_id', $walletId)->orderBy('created_at', 'asc')->get();
if ($txs->count() === 0) {
    echo "(none)\n";
} else {
    foreach ($txs as $t) {
        echo implode('|', [
            $t->id,
            $t->type,
            (string)$t->amount,
            $t->status,
            $t->reference ?? '',
            $t->created_at ?? ''
        ]) . PHP_EOL;
    }
}
