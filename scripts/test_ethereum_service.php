<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\EthereumService;
use App\Models\Wallet;

// Read-only test for EthereumService
$walletId = 186;
$wallet = Wallet::find($walletId);
if (!$wallet) {
    echo json_encode(['error' => 'Wallet not found', 'wallet_id' => $walletId]) . PHP_EOL;
    exit(0);
}

$eth = new EthereumService();
$address = $wallet->wallet_address;
$contract = env('USDT_CONTRACT_ADDRESS');

$result = [
    'wallet_id' => $walletId,
    'address' => $address,
    'usdt_contract' => $contract,
    'checks' => []
];

try {
    $result['checks']['isValidAddress'] = $eth->isValidAddress($address);
} catch (\Throwable $e) {
    $result['checks']['isValidAddress_error'] = $e->getMessage();
}

try {
    $result['checks']['eth_balance'] = $eth->getBalance($address);
} catch (\Throwable $e) {
    $result['checks']['eth_balance_error'] = $e->getMessage();
}

if (!empty($contract)) {
    try {
        $result['checks']['usdt_onchain_balance'] = $eth->getTokenBalance($contract, $address);
    } catch (\Throwable $e) {
        $result['checks']['usdt_onchain_balance_error'] = $e->getMessage();
    }
} else {
    $result['checks']['usdt_onchain_balance'] = 'USDT_CONTRACT_ADDRESS not set';
}

// get current block and a diagnose call to fetch gasPrice
try {
    $result['checks']['current_block'] = $eth->getCurrentBlockNumber();
} catch (\Throwable $e) {
    $result['checks']['current_block_error'] = $e->getMessage();
}

try {
    // Diagnostic call to obtain gasPrice (ETH estimate); not signing anything
    // Use a tiny amount for diagnose; this is read-only
    $diag = $eth->estimateGas($address, '0x360Fd699e7BF73383552fE5A8642D549489A53F9', '0.000001');
    $result['checks']['diagnose'] = $diag;
} catch (\Throwable $e) {
    $result['checks']['diagnose_error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(0);
