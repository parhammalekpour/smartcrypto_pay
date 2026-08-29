<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

$txHash = '0xda0828d942940b69b408c9947fc55e9fdb88cc1b72618c489c9712e4860e3aba';

$out = ['tx' => null, 'wallet' => null, 'reserved' => null, 'other' => null];

$tx = Transaction::where('tx_hash', $txHash)->first();
if (!$tx) {
    echo json_encode(['error' => 'transaction_not_found', 'tx_hash' => $txHash]) . PHP_EOL;
    exit(1);
}

$out['tx'] = $tx->toArray();

$wallet = $tx->wallet;
if ($wallet) {
    $out['wallet'] = $wallet->toArray();

    // calculate reserved sum for this wallet (processing, broadcasting, pending)
    $reservedRow = DB::selectOne('select coalesce(sum(amount),0) as total from transactions where wallet_id = ? and status in (?, ?, ?)', [$wallet->id, 'processing', 'broadcasting', 'pending']);
    $reserved = (string) ($reservedRow->total ?? '0');
    $out['reserved'] = $reserved;

    // list pending outbound txs for wallet
    $outs = Transaction::where('wallet_id', $wallet->id)->whereIn('status', ['processing','broadcasting','pending','completed'])->get()->map(function($t){ return $t->only(['id','type','amount','status','tx_hash','created_at','broadcasted_at','confirmed_at']); });
    $out['outgoing_transactions'] = $outs;
} else {
    $out['wallet'] = null;
}

// also check if there is a transaction row with same tx_hash but different type
$matches = Transaction::where('tx_hash', $txHash)->get()->map(function($t){ return $t->only(['id','type','status','wallet_id','amount']); });
$out['matches_by_txhash'] = $matches;

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
exit(0);
