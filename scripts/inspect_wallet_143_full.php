<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\Deposit;
use App\Models\Wallet;

$walletId = 143;

$wallet = Wallet::find($walletId);
if ($wallet) {
    echo "WALLET: " . json_encode([
        'id' => $wallet->id,
        'address' => $wallet->wallet_address,
        'currency' => $wallet->currency,
        'balance' => (string)$wallet->balance,
        'last_scanned_block' => $wallet->last_scanned_block ?? null,
        'created_at' => (string)$wallet->created_at,
        'updated_at' => (string)$wallet->updated_at
    ]) . PHP_EOL . PHP_EOL;
} else {
    echo "Wallet not found\n";
}

$txs = Transaction::where('wallet_id', $walletId)->orderBy('created_at', 'asc')->get();
echo "TRANSACTIONS (full):\n";
foreach ($txs as $t) {
    echo json_encode($t->toArray()) . PHP_EOL;
}

$references = $txs->pluck('reference')->unique()->filter()->values()->all();
if (!empty($references)) {
    echo PHP_EOL . "CHECKING DEPOSITS matching transaction references:\n";
    foreach ($references as $ref) {
        $d = Deposit::where('tx_hash', $ref)->first();
        echo "reference={$ref} -> ";
        if ($d) echo json_encode($d->toArray()) . PHP_EOL; else echo "(no deposit with tx_hash={$ref})\n";
    }
}
