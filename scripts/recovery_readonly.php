<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = '0xeedffea149f7fea90436d6637430e138a9e9868f586840ecae43d57f4597ecbc';
$w = App\Models\Wallet::find(186);
if (!$w) { echo json_encode(['error'=>'WALLET_NOT_FOUND']) . PHP_EOL; exit(1); }

$out1 = ['id'=>$w->id, 'address'=>$w->wallet_address, 'balance'=>$w->balance, 'last_scanned_block'=>$w->last_scanned_block];
$out2 = ['deposit_exists'=> (bool)App\Models\Deposit::where('tx_hash',$tx)->exists(), 'tx_exists'=> (bool)App\Models\Transaction::where('reference',$tx)->where('type','deposit')->exists()];

echo json_encode(['wallet'=>$out1,'checks'=>$out2]) . PHP_EOL;
