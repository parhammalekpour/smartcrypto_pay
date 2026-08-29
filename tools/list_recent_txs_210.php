<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$rows = Transaction::where('wallet_id', 210)
    ->orderBy('id', 'desc')
    ->take(10)
    ->get();

$out = [];
foreach ($rows as $t) {
    $out[] = [
        'id' => $t->id,
        'amount' => (string)$t->amount,
        'status' => $t->status,
        'tx_hash' => $t->tx_hash,
        'created_at' => (string)$t->created_at,
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
