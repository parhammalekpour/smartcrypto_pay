<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$tx = Transaction::where('wallet_id',210)->where('amount','0.001')->orderBy('id','desc')->first();
if (!$tx) { echo json_encode(['error'=>'tx_not_found']) . PHP_EOL; exit(1); }
$out = $tx->toArray();
$out['wallet'] = $tx->wallet ? $tx->wallet->toArray() : null;
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
