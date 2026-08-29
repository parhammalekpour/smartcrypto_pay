<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Transaction;

$wallet = Wallet::find(186);
if (!$wallet) { echo json_encode(['error'=>'wallet_not_found']); exit(1); }

$out = [
    'id' => $wallet->id,
    'address' => $wallet->wallet_address,
    'currency' => $wallet->currency,
    'network' => $wallet->network,
    'balance' => $wallet->balance,
    'last_scanned_block' => $wallet->last_scanned_block,
    'encrypted_private_key_present' => !empty($wallet->encrypted_private_key),
    'owner_type' => $wallet->owner_type,
    'owner_id' => $wallet->owner_id,
];

// check deposits for our tx
$tx = '0x77b79d78d571080057ce2494d393bdcb74d3feb267fde6291d5a5e897614b252';
$deposit = Deposit::where('tx_hash',$tx)->first();
$out['deposit_exists'] = $deposit ? true : false;
if ($deposit) {
    $out['deposit'] = [
        'id' => $deposit->id,
        'wallet_id' => $deposit->wallet_id,
        'amount' => $deposit->amount,
        'status' => $deposit->status,
        'confirmations' => $deposit->confirmations,
        'processed_at' => $deposit->processed_at,
        'block_number' => $deposit->block_number,
    ];
}

// check transactions referencing tx
$txrec = Transaction::where('reference',$tx)->orWhere('tx_hash',$tx)->first();
$out['transaction_exists'] = $txrec ? true : false;
if ($txrec) {
    $out['transaction'] = [
        'id' => $txrec->id,
        'wallet_id' => $txrec->wallet_id,
        'type' => $txrec->type,
        'status' => $txrec->status,
        'amount' => $txrec->amount,
        'reference' => $txrec->reference,
        'tx_hash' => $txrec->tx_hash,
    ];
}

// list recent deposits for wallet
$recentDeposits = Deposit::where('wallet_id',186)->orderBy('id','desc')->take(10)->get()->map(function($d){ return ['id'=>$d->id,'tx'=>$d->tx_hash,'amount'=>$d->amount,'status'=>$d->status,'block'=>$d->block_number]; });
$out['recent_deposits'] = $recentDeposits;

// wallet sync eligibility: currency in USDT?
$out['eligible_for_usdt_scan'] = in_array(strtoupper($wallet->currency), ['USDT','USD']);

// env USDT contract
$out['env_usdt_contract'] = env('USDT_CONTRACT_ADDRESS');

// print
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(0);
