<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\Wallet;

$walletAddress = '0xA60F27286662A141Ac9D5105541ccC52DfB7D044';
$wallet = Wallet::whereRaw('lower(wallet_address) = ?', [strtolower($walletAddress)])->first();
if (!$wallet) { echo json_encode(['error'=>'wallet_not_found']).PHP_EOL; exit(1); }

$out = ['wallet_id' => $wallet->id, 'wallet_address' => $wallet->wallet_address, 'balance' => $wallet->balance, 'transactions' => []];
$txs = Transaction::where('wallet_id', $wallet->id)->orderBy('created_at','desc')->get();
foreach ($txs as $t) {
    $out['transactions'][] = $t->only(['id','type','amount','status','tx_hash','created_at','broadcasted_at','confirmed_at','failure_reason']);
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
exit(0);
