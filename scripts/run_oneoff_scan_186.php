<?php
// One-off scan for Wallet ID 186 to detect/process USDT deposit and update DB balance
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BlockchainDepositService;
use App\Services\EthereumService;
use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Transaction;

$walletId = 186;
$limit = 50;

$ethService = new EthereumService();
$scanner = new BlockchainDepositService($ethService);

$result = $scanner->scanOnce($limit, $walletId);
// also process any confirmed-but-unprocessed deposits for good measure
$processed = $scanner->processPendingConfirmedDeposits();

// Fetch wallet and related records
$wallet = Wallet::find($walletId);
$txHash = '0x77b79d78d571080057ce2494d393bdcb74d3feb267fde6291d5a5e897614b252';
$deposit = Deposit::where('tx_hash', $txHash)->first();
$tx = Transaction::where('reference', $txHash)->orWhere('tx_hash', $txHash)->first();

// Read on-chain token balance
$onchainBalance = null;
try {
    $contract = env('USDT_CONTRACT_ADDRESS');
    if ($contract) {
        $onchainBalance = $ethService->getTokenBalance($contract, $wallet->wallet_address);
    }
} catch (\Throwable $e) {
    $onchainBalance = 'error:' . $e->getMessage();
}

$out = [
    'scan_result' => $result,
    'process_pending_confirmed' => $processed,
    'wallet' => [
        'id' => $wallet->id,
        'address' => $wallet->wallet_address,
        'currency' => $wallet->currency,
        'network' => $wallet->network,
        'balance_db' => $wallet->balance,
        'last_scanned_block' => $wallet->last_scanned_block
    ],
    'deposit' => $deposit ? [ 'id' => $deposit->id, 'tx_hash' => $deposit->tx_hash, 'amount' => $deposit->amount, 'status' => $deposit->status, 'processed_at' => $deposit->processed_at, 'confirmations' => $deposit->confirmations ] : null,
    'transaction' => $tx ? [ 'id' => $tx->id, 'type' => $tx->type, 'status' => $tx->status, 'amount' => $tx->amount, 'reference' => $tx->reference, 'tx_hash' => $tx->tx_hash ] : null,
    'onchain_balance' => $onchainBalance
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(0);
