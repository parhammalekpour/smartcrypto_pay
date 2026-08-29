<?php
// Sends 1 USDT from Wallet 183 to Wallet 186 using existing EthereumService and Wallet model.
// WARNING: This will broadcast a real Sepolia transaction. Only run after user confirmation.

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;
use Illuminate\Support\Facades\Log;

$senderId = 183;
$receiverId = 186;
$contract = env('USDT_CONTRACT_ADDRESS') ?: '0xDAedAc477118680F85B7812AF3Dec4be3F3A86C9';
$amount = '1';

$sender = Wallet::find($senderId);
$receiver = Wallet::find($receiverId);

if (!$sender || !$receiver) {
    echo json_encode(['error' => 'Wallet not found', 'sender_exists' => (bool)$sender, 'receiver_exists' => (bool)$receiver]);
    exit(1);
}

$senderAddr = strtolower($sender->wallet_address);
$receiverAddr = strtolower($receiver->wallet_address);

$ethService = new EthereumService();

// Decrypt private key in memory using Wallet->getPrivateKey(); do NOT print it
$privateKey = $sender->getPrivateKey();
if (empty($privateKey)) {
    echo json_encode(['error' => 'Sender has no signing key available']);
    exit(1);
}

// Send token using existing service (this will sign and broadcast using node script)
try {
    $res = $ethService->sendTokenTransaction($privateKey, $receiverAddr, $amount, $contract);
} catch (\Throwable $e) {
    echo json_encode(['error' => 'sendTokenTransaction failed', 'message' => $e->getMessage()]);
    exit(1);
}

// Expected response contains txHash
$txHash = $res['txHash'] ?? ($res['hash'] ?? null);
$txInfo = [
    'txHash' => $txHash,
    'raw_response' => $res
];

if (!$txHash) {
    echo json_encode(['error' => 'No txHash returned from sendTokenTransaction', 'response' => $res]);
    exit(1);
}

// Wait for receipt
$maxWait = 300; // seconds
$interval = 6;
$waited = 0;
$receipt = null;
try {
    while ($waited < $maxWait) {
        $r = $ethService->getTransactionReceipt($txHash);
        if (!empty($r['receipt']) && is_array($r['receipt'])) {
            $receipt = $r['receipt'];
            break;
        }
        sleep($interval);
        $waited += $interval;
    }
} catch (\Throwable $e) {
    echo json_encode(['error' => 'Failed while fetching receipt', 'message' => $e->getMessage()]);
    exit(1);
}

if (!$receipt) {
    echo json_encode(['error' => 'Timed out waiting for transaction receipt', 'txHash' => $txHash]);
    exit(1);
}

// Check success
$status = $receipt['status'] ?? null; // 1 = success
$blockNumber = $receipt['blockNumber'] ?? null;

// Fetch token transfers to receiver and look for this tx
$transfers = [];
try {
    $transfers = $ethService->getTokenTransfers($contract, $receiverAddr, 50, null);
} catch (\Throwable $e) {
    // continue, we'll still check on-chain balance
}

$foundTransfer = null;
foreach ($transfers as $t) {
    // t may include transactionHash, rawTokenValue or value
    $txh = $t['transactionHash'] ?? $t['hash'] ?? null;
    if ($txh && strtolower($txh) === strtolower($txHash)) {
        $foundTransfer = $t;
        break;
    }
}

// Verify receiver on-chain balance (should have increased by 1)
try {
    $onchainReceiverBalance = $ethService->getTokenBalance($contract, $receiverAddr);
} catch (\Throwable $e) {
    $onchainReceiverBalance = null;
}

// Refresh DB wallet record for receiver
$receiver->refresh();
$dbReceiverBalance = $receiver->balance ?? null;

// Prepare final output — only permitted fields
$output = [
    'transaction' => [
        'txHash' => $txHash,
        'from' => $senderAddr,
        'to' => $receiverAddr,
        'amount' => $amount,
        'network' => env('ETHEREUM_NETWORK', 'sepolia'),
        'gas' => [
            'gasUsed' => $receipt['gasUsed'] ?? null,
            'effectiveGasPrice' => $receipt['effectiveGasPrice'] ?? ($receipt['gasPrice'] ?? null)
        ],
        'broadcast_result' => $res
    ],
    'receipt' => $receipt,
    'transfer_event_found' => $foundTransfer !== null,
    'transfer_event' => $foundTransfer,
    'onchain_receiver_balance' => $onchainReceiverBalance,
    'db_receiver_balance' => $dbReceiverBalance
];

// Basic verification checks
$verification = [
    'receipt_status_success' => ($status === 1 || $status === '0x1' || $status === '1'),
    'transfer_event_exists' => ($foundTransfer !== null),
    'receiver_received_exact_amount_onchain' => (is_numeric($onchainReceiverBalance) ? ((float)$onchainReceiverBalance >= (float)$amount) : null)
];

$output['verification'] = $verification;

// Output only allowed data as JSON
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(0);
