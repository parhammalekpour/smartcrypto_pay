<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\EthereumService;

$walletId = 210;
$wallet = Wallet::find($walletId);
if (!$wallet) {
    echo json_encode(['error' => 'wallet_not_found', 'wallet_id' => $walletId]) . PHP_EOL;
    exit(1);
}

$balance = (string)$wallet->balance;

// Reserved outgoing statuses used in processEthWithdrawalV2
$statuses = ['processing','broadcasting','pending','completed','confirmed'];
$reservedTotalRow = Transaction::where('wallet_id', $walletId)->whereIn('status', $statuses)->selectRaw('coalesce(sum(amount),0) as total')->first();
$reserved = (string)($reservedTotalRow->total ?? '0');

$reservedTxs = Transaction::where('wallet_id', $walletId)->whereIn('status', $statuses)->orderBy('created_at','desc')->get()->map(function($t){
    return [
        'id' => $t->id,
        'amount' => (string)$t->amount,
        'status' => $t->status,
        'tx_hash' => $t->tx_hash,
        'created_at' => (string)$t->created_at,
    ];
});

$eth = new EthereumService();
try {
    $onchain = trim((string)$eth->getBalance($wallet->wallet_address));
} catch (\Throwable $e) {
    $onchain = null;
    $onchain_error = $e->getMessage();
}

// Calculate available = dbBalance - reserved
// Use BCMath with 18 decimals
function bcsub_prec($a, $b) { return bcsub($a, $b, 18); }
function bccomp_prec($a, $b) { return bccomp($a, $b, 18); }
$available = bcsub_prec($balance, $reserved);

// Required amount for the attempted withdrawal (0.001), per request
$requested = '0.001';
$fails_db_lock = bccomp_prec($available, $requested) < 0;

$out = [
    'wallet_id' => $walletId,
    'wallet_address' => $wallet->wallet_address,
    'db_balance' => $balance,
    'reserved_statuses' => $statuses,
    'reserved_total' => $reserved,
    'reserved_txs_count' => count($reservedTxs),
    'reserved_txs' => $reservedTxs,
    'available_after_reserved' => $available,
    'requested_amount' => $requested,
    'db_lock_check_passes' => $fails_db_lock ? false : true,
    'onchain_balance' => $onchain ?? null,
];
if (isset($onchain_error)) $out['onchain_error'] = $onchain_error;

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
