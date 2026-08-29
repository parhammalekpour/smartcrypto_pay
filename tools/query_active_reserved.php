<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$walletId = 210;
$statuses = ['processing','broadcasting','pending'];
$row = Transaction::where('wallet_id', $walletId)->whereIn('status', $statuses)->selectRaw('coalesce(sum(amount),0) as total')->first();
$txs = Transaction::where('wallet_id', $walletId)->whereIn('status', $statuses)->orderBy('created_at','desc')->get()->map(function($t){
    return [
        'id' => $t->id,
        'amount' => (string)$t->amount,
        'status' => $t->status,
        'tx_hash' => $t->tx_hash,
        'created_at' => (string)$t->created_at,
    ];
});
$out = [
    'wallet_id' => $walletId,
    'statuses' => $statuses,
    'reserved_active_total' => (string)($row->total ?? '0'),
    'reserved_active_count' => count($txs),
    'reserved_active_txs' => $txs,
];
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
