<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;

$walletAddress = '0xA60F27286662A141Ac9D5105541ccC52DfB7D044';
$dest = '0x09a09538c11f170a537564e55efe3f824d1583a1';
$amount = '0.001';

$output = [
    'wallet' => $walletAddress,
    'destination' => $dest,
    'amount' => $amount,
    'found' => false,
    'derived_match' => false,
    'derived_address' => null,
    'balance' => null,
    'nonce' => null,
    'feeData' => null,
    'estimate' => null,
    'sendResult' => null,
    'error' => null,
];

try {
    $wallet = Wallet::whereRaw('lower(wallet_address) = ?', [strtolower($walletAddress)])->first();
    if (!$wallet) {
        $output['error'] = 'wallet_not_found';
        echo json_encode($output) . PHP_EOL;
        exit(2);
    }
    $output['found'] = true;

    // Decrypt private key in memory (Wallet::getPrivateKey) but do not expose it.
    $privateKey = $wallet->getPrivateKey();
    if ($privateKey === null) {
        $output['error'] = 'could_not_decrypt_private_key_or_invalid';
        echo json_encode($output) . PHP_EOL;
        exit(3);
    }

    $eth = new EthereumService();

    // Derive signer address via EthereumService (this uses node script under the hood)
    try {
        $derived = $eth->getSignerAddress($privateKey);
        $output['derived_address'] = $derived;
        $output['derived_match'] = (strtolower($derived) === strtolower($walletAddress));
    } catch (Throwable $e) {
        $output['error'] = 'derive_failed';
        $output['error_detail'] = $e->getMessage();
        echo json_encode($output) . PHP_EOL;
        exit(4);
    }

    // Get balance
    try {
        $balance = $eth->getBalance($walletAddress);
        $output['balance'] = $balance;
    } catch (Throwable $e) {
        $output['error'] = 'balance_read_failed';
        $output['error_detail'] = $e->getMessage();
        echo json_encode($output) . PHP_EOL;
        exit(5);
    }

    // Get nonce (transaction count pending)
    try {
        $nonce = $eth->getTransactionCount($walletAddress, 'pending');
        $output['nonce'] = $nonce;
    } catch (Throwable $e) {
        $output['error'] = 'nonce_read_failed';
        $output['error_detail'] = $e->getMessage();
        echo json_encode($output) . PHP_EOL;
        exit(6);
    }

    // Fee data
    try {
        $fee = $eth->getFeeData();
        $output['feeData'] = $fee;
    } catch (Throwable $e) {
        $output['error'] = 'feeData_failed';
        $output['error_detail'] = $e->getMessage();
        echo json_encode($output) . PHP_EOL;
        exit(7);
    }

    // Estimate gas
    try {
        $estimate = $eth->estimateGas($walletAddress, $dest, $amount);
        $output['estimate'] = $estimate;
    } catch (Throwable $e) {
        $output['error'] = 'estimate_failed';
        $output['error_detail'] = $e->getMessage();
        echo json_encode($output) . PHP_EOL;
        exit(8);
    }

    // Attempt send (this will use EthereumService->sendTransaction which runs node script with PRIVATE_KEY env)
    try {
        $res = $eth->sendTransaction($privateKey, $dest, $amount);
        $output['sendResult'] = $res;
    } catch (Throwable $e) {
        // Provide exact exception message
        $output['error'] = 'send_failed';
        $output['error_detail'] = $e->getMessage();
        echo json_encode($output) . PHP_EOL;
        exit(9);
    }

    echo json_encode($output) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    $output['error'] = 'unexpected';
    $output['error_detail'] = $e->getMessage();
    echo json_encode($output) . PHP_EOL;
    exit(99);
}
