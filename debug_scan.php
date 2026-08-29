<?php

$boot = require_once 'bootstrap/app.php';
$app = $boot->make('Illuminate\Contracts\Console\Kernel');
$app->bootstrap();

echo "=== BLOCKCHAIN SCANNER INVESTIGATION ===" . PHP_EOL . PHP_EOL;

echo "1. WALLET 186 STATUS:" . PHP_EOL;
$wallet = \App\Models\Wallet::find(186);
if ($wallet) {
    echo "   Address: " . $wallet->wallet_address . PHP_EOL;
    echo "   Currency: " . $wallet->currency . PHP_EOL;
    echo "   Last Scanned Block: " . ($wallet->last_scanned_block ?? 'NULL') . PHP_EOL;
} else {
    echo "   ERROR: Wallet 186 not found" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "2. CURRENT BLOCK:" . PHP_EOL;
try {
    $ethService = new \App\Services\EthereumService();
    $currentBlock = $ethService->getCurrentBlockNumber();
    echo "   Current Block: " . $currentBlock . PHP_EOL;
} catch (\Exception $e) {
    echo "   ERROR getting current block: " . $e->getMessage() . PHP_EOL;
    $currentBlock = null;
}

echo PHP_EOL . "3. SCAN RANGE CALCULATION:" . PHP_EOL;
$fromBlock = (!empty($wallet->last_scanned_block) && is_numeric($wallet->last_scanned_block)) 
    ? $wallet->last_scanned_block + 1 
    : 0;
echo "   From Block: " . $fromBlock . PHP_EOL;
echo "   To Block: " . ($currentBlock ?? 'UNKNOWN') . PHP_EOL;
if ($currentBlock) {
    echo "   Target Block (11521497): " . (($fromBlock <= 11521497 && 11521497 <= $currentBlock) ? "IN RANGE" : "OUT OF RANGE") . PHP_EOL;
}

echo PHP_EOL . "4. CONFIGURATION:" . PHP_EOL;
echo "   CHUNK_SIZE: 2000" . PHP_EOL;
echo "   Confirmation Threshold: " . config('ethereum.confirmation_threshold') . PHP_EOL;
echo "   USDT Contract: " . env('USDT_CONTRACT_ADDRESS') . PHP_EOL;
echo "   Network: " . env('ETHEREUM_NETWORK') . PHP_EOL;

echo PHP_EOL . "5. CHECK FOR EXISTING DEPOSIT:" . PHP_EOL;
$txHash = '0xeedffea149f7fea90436d6637430e138a9e9868f586840ecae43d57f4597ecbc';
$existingDeposit = \App\Models\Deposit::where('tx_hash', $txHash)->first();
if ($existingDeposit) {
    echo "   FOUND: Deposit ID=" . $existingDeposit->id . PHP_EOL;
    echo "   Status: " . $existingDeposit->status . PHP_EOL;
    echo "   Currency: " . $existingDeposit->currency . PHP_EOL;
    echo "   Amount: " . $existingDeposit->amount . PHP_EOL;
} else {
    echo "   NOT FOUND in database" . PHP_EOL;
}
?>
