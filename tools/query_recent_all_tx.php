<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$rows = Transaction::where('wallet_id', 210)->orderBy('created_at', 'desc')->take(20)->get();
foreach ($rows as $r) {
    echo json_encode([
        'id' => $r->id,
        'wallet_id' => $r->wallet_id,
        'amount' => (string)$r->amount,
        'status' => $r->status,
        'tx_hash' => $r->tx_hash,
        'nonce' => $r->nonce,
        'created_at' => (string)$r->created_at,
        'updated_at' => (string)$r->updated_at,
    ]) . PHP_EOL;
}

if (count($rows) === 0) echo "NO_TRANSACTIONS\n";
