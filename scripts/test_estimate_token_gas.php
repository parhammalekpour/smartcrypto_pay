<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\EthereumService;
use App\Models\Wallet;

$walletId = 186;
$wallet = Wallet::find($walletId);
if (!$wallet) {
    echo json_encode(['error' => 'Wallet not found', 'wallet_id' => $walletId]) . PHP_EOL;
    exit(0);
}

$eth = new EthereumService();
$contract = env('USDT_CONTRACT_ADDRESS');
$from = $wallet->wallet_address;
$to = '0x360Fd699e7BF73383552fE5A8642D549489A53F9';
$amount = '0.9';

try {
    $res = $eth->estimateTokenGas($contract, $from, $to, $amount, 6);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]) . PHP_EOL;
}

exit(0);
