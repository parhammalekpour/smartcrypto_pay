<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$txHash = '0xeedffea149f7fea90436d6637430e138a9e9868f586840ecae43d57f4597ecbc';
$wallet = App\Models\Wallet::find(186);
if (!$wallet) { echo json_encode(['error'=>'WALLET_NOT_FOUND']) . PHP_EOL; exit(1); }

$eth = new App\Services\EthereumService();
$contract = env('USDT_CONTRACT_ADDRESS');
$currentBlock = $eth->getCurrentBlockNumber();
$transfers = $eth->getTokenTransfersInRange($contract, $wallet->wallet_address, 11521497, 11521497);

$out = ['wallet_id'=>$wallet->id,'wallet_address'=>$wallet->wallet_address,'wallet_currency'=>$wallet->currency,'wallet_owner_type'=>$wallet->owner_type,'wallet_owner_id'=>$wallet->owner_id,'wallet_user_id'=>$wallet->user_id,'wallet_last_scanned_block'=>$wallet->last_scanned_block,'current_block'=>$currentBlock,'transfers_count'=>count($transfers)];

$checks = [];

foreach ($transfers as $tx) {
    $txHashLocal = $tx['hash'] ?? ($tx['transactionHash'] ?? null);
    $to = $tx['to'] ?? null;
    $amount = $tx['value'] ?? null;
    $blockNumber = isset($tx['blockNumber']) ? (int)$tx['blockNumber'] : null;
    $confirmations = ($currentBlock !== null && $blockNumber !== null) ? max(0, $currentBlock - $blockNumber + 1) : 0;

    $check = ['txHash'=>$txHashLocal,'to'=>$to,'amount'=>$amount,'blockNumber'=>$blockNumber,'confirmations'=>$confirmations];

    // recipient check
    $check['to_exists'] = isset($tx['to']);
    $check['to_matches_wallet'] = $check['to_exists'] ? (strtolower($to) === strtolower($wallet->wallet_address)) : false;

    // tx hash
    $check['txHash_exists'] = (bool)$txHashLocal;

    // duplicate deposit
    $check['duplicate_deposit_exists'] = App\Models\Deposit::where('tx_hash', $txHashLocal)->where('currency', 'USDT')->exists();

    // amount validation
    $check['amount_is_numeric'] = is_numeric($amount);

    // owner mapping
    $userId = null; $merchantId = null;
    if (!empty($wallet->owner_type) && !empty($wallet->owner_id)) {
        if (strtolower($wallet->owner_type) === 'user') $userId = $wallet->owner_id;
        elseif (strtolower($wallet->owner_type) === 'merchant') $merchantId = $wallet->owner_id;
    } else {
        if (!empty($wallet->user_id)) $userId = $wallet->user_id;
    }
    $check['userId']=$userId; $check['merchantId']=$merchantId;

    // currency
    $check['currency'] = 'USDT';

    // Skipped reasons per code: to missing, to mismatch, no txHash, duplicate exists
    $skipReasons = [];
    if (!$check['to_exists']) $skipReasons[] = 'missing_to';
    if (!$check['to_matches_wallet']) $skipReasons[] = 'to_mismatch';
    if (!$check['txHash_exists']) $skipReasons[] = 'missing_txhash';
    if ($check['duplicate_deposit_exists']) $skipReasons[] = 'duplicate_deposit';
    if (!$check['amount_is_numeric']) $skipReasons[] = 'bad_amount';

    $check['would_create_deposit'] = empty($skipReasons);
    $check['skip_reasons'] = $skipReasons;

    $checks[] = $check;
}

echo json_encode(['summary'=>$out,'checks'=>$checks], JSON_PRETTY_PRINT) . PHP_EOL;
