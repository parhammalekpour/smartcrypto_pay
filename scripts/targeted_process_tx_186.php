<?php
// Targeted processing for known TX into Wallet 186
// 1) Verify wallet and on-chain tx
// 2) Set wallet.last_scanned_block = block-1 (11513831)
// 3) Run scanOnce for the single wallet
// 4) Process pending confirmed deposits
// 5) Report required fields

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Services\EthereumService;
use App\Services\BlockchainDepositService;

$txHash = '0x77b79d78d571080057ce2494d393bdcb74d3feb267fde6291d5a5e897614b252';
$expectedBlock = 11513832;
$walletId = 186;
$contract = env('USDT_CONTRACT_ADDRESS');

// Load wallet
$wallet = Wallet::find($walletId);
if (!$wallet) { echo json_encode(['error'=>'wallet_not_found']); exit(1); }

// Verify wallet fields
$ok = true;
$errors = [];
if (strtoupper($wallet->currency) !== 'USDT') { $ok = false; $errors[] = 'wallet currency is not USDT'; }
if (strtolower($wallet->wallet_address) !== strtolower('0x6AB6f22AfCca3b4AEdc26E834815d47cca590Fcd')) { $ok = false; $errors[] = 'wallet address mismatch'; }
if (empty($contract) || strtolower($contract) !== strtolower('0xDAedAc477118680F85B7812AF3Dec4be3F3A86C9')) { $ok = false; $errors[] = 'USDT_CONTRACT_ADDRESS mismatch or empty'; }

// Verify tx on-chain
$eth = new EthereumService();
try {
    $receiptRes = $eth->getTransactionReceipt($txHash);
} catch (\Throwable $e) {
    echo json_encode(['error'=>'failed_get_receipt','message'=>$e->getMessage()]); exit(1);
}
$receipt = $receiptRes['receipt'] ?? null;
if (!$receipt) { echo json_encode(['error'=>'no_receipt_found']); exit(1); }

// Extract block number
$blockNumber = null;
if (isset($receipt['blockNumber'])) {
    $bn = $receipt['blockNumber'];
    if (is_string($bn) && str_starts_with($bn,'0x')) $blockNumber = hexdec($bn); else $blockNumber = (int)$bn;
}
if ($blockNumber !== $expectedBlock) { $ok = false; $errors[] = 'tx block mismatch: expected ' . $expectedBlock . ' got ' . $blockNumber; }

// Check Transfer event to wallet
$hasTransfer = false;
$transferAmount = null;
if (!empty($receipt['logs']) && is_array($receipt['logs'])) {
    foreach ($receipt['logs'] as $log) {
        // topic[0] == Transfer signature
        $topics = $log['topics'] ?? [];
        if (isset($topics[0]) && strtolower($topics[0]) === strtolower('0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef')) {
            // check to topic
            $toTopic = $topics[2] ?? null;
            if ($toTopic) {
                // last 40 hex chars
                $toAddr = '0x' . substr($toTopic, -40);
                if (strtolower($toAddr) === strtolower($wallet->wallet_address)) {
                    // parse data (uint256)
                    $data = $log['data'] ?? null;
                    if ($data && is_string($data)) {
                        $val = null;
                        if (str_starts_with($data,'0x')) $hex = substr($data,2); else $hex = $data;
                        $val = hexdec($hex);
                        // USDT decimals 6 -> units
                        $transferAmount = $val / pow(10,6);
                        if (abs($transferAmount - 1.0) < 0.0000001) {
                            $hasTransfer = true;
                        }
                    }
                }
            }
        }
    }
}
if (!$hasTransfer) { $ok = false; $errors[] = 'No matching Transfer event to wallet with amount 1 USDT'; }

if (!$ok) {
    echo json_encode(['verified'=>false,'errors'=>$errors], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit(1);
}

// All verifications passed. Now set last_scanned_block = expectedBlock - 1
$wallet->last_scanned_block = $expectedBlock - 1;
$wallet->save();

// Run the scan once for this wallet
$scanner = new BlockchainDepositService($eth);
$scanResult = $scanner->scanOnce(50, $walletId);
$processResult = $scanner->processPendingConfirmedDeposits();

// Read back required fields
$wallet->refresh();
$deposit = Deposit::where('tx_hash',$txHash)->first();
$transaction = Transaction::where('reference',$txHash)->orWhere('tx_hash',$txHash)->first();
$onchainBalance = $eth->getTokenBalance($contract, $wallet->wallet_address);

$out = [
    'wallet_db_balance' => $wallet->balance,
    'wallet_last_scanned_block' => $wallet->last_scanned_block,
    'deposit' => $deposit ? ['id'=>$deposit->id,'tx_hash'=>$deposit->tx_hash,'amount'=>$deposit->amount,'status'=>$deposit->status,'confirmations'=>$deposit->confirmations] : null,
    'transaction' => $transaction ? ['id'=>$transaction->id,'type'=>$transaction->type,'status'=>$transaction->status,'amount'=>$transaction->amount,'reference'=>$transaction->reference,'tx_hash'=>$transaction->tx_hash] : null,
    'deposit_status' => $deposit->status ?? null,
    'transaction_status' => $transaction->status ?? null,
    'onchain_usdt_balance' => $onchainBalance,
    'balances_match' => ((string)$wallet->balance === (string)$onchainBalance)
];

echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(0);
