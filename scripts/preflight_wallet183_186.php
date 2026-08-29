<?php
// Preflight script for Wallet 183 -> Wallet 186 1 USDT transfer (read-only safe info)
// Loads Laravel app, fetches wallets, queries balances and token gas estimate via existing services and node helper.

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
// bootstrap the framework
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;

$senderId = 183;
$receiverId = 186;
$contract = env('USDT_CONTRACT_ADDRESS') ?: '0xDAedAc477118680F85B7812AF3Dec4be3F3A86C9';
$amount = '1'; // 1 USDT human-readable
$network = env('ETHEREUM_NETWORK', 'sepolia');

// Fetch wallets from DB
$sender = Wallet::find($senderId);
$receiver = Wallet::find($receiverId);

if (!$sender || !$receiver) {
    echo json_encode(['error' => 'Wallet not found', 'sender_exists' => (bool)$sender, 'receiver_exists' => (bool)$receiver]);
    exit(1);
}

// Verify addresses match expectations (safety)
$expectedSender = strtolower('0x7B48DC6A00C1c93eB3B28feB27A319943b039f3b');
$expectedReceiver = strtolower('0x6AB6f22AfCca3b4AEdc26E834815d47cca590Fcd');

$senderAddr = strtolower($sender->wallet_address ?: '');
$receiverAddr = strtolower($receiver->wallet_address ?: '');

if ($senderAddr !== $expectedSender) {
    echo json_encode(['error' => 'Sender wallet address mismatch', 'db' => $senderAddr, 'expected' => $expectedSender]);
    exit(1);
}
if ($receiverAddr !== $expectedReceiver) {
    echo json_encode(['error' => 'Receiver wallet address mismatch', 'db' => $receiverAddr, 'expected' => $expectedReceiver]);
    exit(1);
}

$ethService = new EthereumService();

// Check that sender has a signing key (this will decrypt in memory inside Wallet->getPrivateKey)
$hasSigningKey = $sender->hasSigningKey();

// Read balances using EthereumService (which uses existing scripts)
try {
    $senderEth = $ethService->getBalance($senderAddr);
} catch (\Throwable $e) {
    echo json_encode(['error' => 'Failed to read sender ETH balance', 'message' => $e->getMessage()]);
    exit(1);
}

try {
    $senderToken = $ethService->getTokenBalance($contract, $senderAddr);
} catch (\Throwable $e) {
    echo json_encode(['error' => 'Failed to read sender token balance', 'message' => $e->getMessage()]);
    exit(1);
}

try {
    $receiverToken = $ethService->getTokenBalance($contract, $receiverAddr);
} catch (\Throwable $e) {
    echo json_encode(['error' => 'Failed to read receiver token balance', 'message' => $e->getMessage()]);
    exit(1);
}

// Use node helper to estimate token gas and retrieve symbol/decimals
$node = (strpos(PHP_OS, 'WIN') === 0) ? 'node' : 'node';
$estimateScript = __DIR__ . '/estimateTokenGas.js';
$cmd = escapeshellcmd($node) . ' ' . escapeshellarg($estimateScript) . ' ' . escapeshellarg($contract) . ' ' . escapeshellarg($senderAddr) . ' ' . escapeshellarg($receiverAddr) . ' ' . escapeshellarg($amount) . ' ' . escapeshellarg($network);

$descriptor = [];
$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__);
if (!is_resource($proc)) {
    echo json_encode(['error' => 'Failed to start node process for gas estimation']);
    exit(1);
}
$out = stream_get_contents($pipes[1]);
$err = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($proc);

if ($exit !== 0) {
    echo json_encode(['error' => 'estimateTokenGas failed', 'stdout' => $out, 'stderr' => $err]);
    exit(1);
}

$est = json_decode($out, true);
if (!$est) {
    echo json_encode(['error' => 'Failed to parse estimateTokenGas output', 'raw' => $out]);
    exit(1);
}

// compute gas cost in ETH using bcmath if possible
$gasPriceWei = isset($est['gasPrice']) ? $est['gasPrice'] : null;
$estimateGas = isset($est['estimateGas']) ? $est['estimateGas'] : null;
$gasCostWei = null;
$gasCostEth = null;
if ($gasPriceWei !== null && $estimateGas !== null && is_numeric($gasPriceWei) && is_numeric($estimateGas)) {
    // big integers as strings
    if (extension_loaded('bcmath')) {
        $gasCostWei = bcmul($gasPriceWei, $estimateGas);
        $gasCostEth = bcdiv($gasCostWei, '1000000000000000000', 18);
    } else {
        // fallback to float (may lose precision)
        $gasCostWei = (string)((int)$gasPriceWei * (int)$estimateGas);
        $gasCostEth = (string)((float)$gasCostWei / 1e18);
    }
}

// Determine checks
$hasEnoughEthForGas = null;
if ($gasCostWei !== null) {
    // convert senderEth (formatted) to wei by multiplying
    // senderEth may be string '0.001' etc
    $senderEthStr = $senderEth;
    if (extension_loaded('bcmath')) {
        $senderWei = bcmul($senderEthStr, '1000000000000000000', 0);
        $hasEnoughEthForGas = bccomp($senderWei, $gasCostWei) >= 0;
    } else {
        $senderWei = (string)floor((float)$senderEthStr * 1e18);
        $hasEnoughEthForGas = ((float)$senderWei >= (float)$gasCostWei);
    }
}

// Check at least 1 USDT
$senderHasAtLeast1 = false;
if (is_numeric($senderToken)) {
    $senderHasAtLeast1 = ((float)$senderToken >= 1.0);
}

// Prepare safe preflight output
$result = [
    'sender' => [
        'wallet_id' => (int)$sender->id,
        'address' => $senderAddr,
        'has_signing_key' => (bool)$hasSigningKey,
        'eth_balance' => $senderEth,
        'usdt_balance' => $senderToken,
    ],
    'receiver' => [
        'wallet_id' => (int)$receiver->id,
        'address' => $receiverAddr,
        'usdt_balance' => $receiverToken,
    ],
    'token' => [
        'contract' => $contract,
        'symbol' => $est['symbol'] ?? null,
        'decimals' => $est['decimals'] ?? null,
        'amount_to_send' => $amount
    ],
    'gas' => [
        'gasPriceWei' => $gasPriceWei,
        'estimateGas' => $estimateGas,
        'gasCostWei' => $gasCostWei,
        'gasCostEth' => $gasCostEth,
        'hasEnoughEthForGas' => $hasEnoughEthForGas
    ],
    'checks' => [
        'contract_valid' => !empty($est['contract']),
        'symbol_is_USDT' => (isset($est['symbol']) ? (strtoupper($est['symbol']) === 'USDT') : null),
        'decimals_is_6' => (isset($est['decimals']) ? ((int)$est['decimals'] === 6) : null),
        'sender_has_at_least_1_usdt' => $senderHasAtLeast1
    ],
    'network' => $network
];

// Output only safe preflight information
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(0);
