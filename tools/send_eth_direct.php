<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\EthereumService;

function out($m) { echo $m . PHP_EOL; }

try {
    $senderAddr = '0xA60F27286662A141Ac9D5105541ccC52DfB7D044';
    $to = '0x360Fd699e7BF73383552fE5A8642D549489A53F9';
    $amount = '0.001';

    out('LOOKUP SENDER: ' . $senderAddr);
    $wallet = Wallet::whereRaw('LOWER(wallet_address) = ?', [strtolower($senderAddr)])->first();
    if (!$wallet) { out('SENDER_WALLET_NOT_FOUND'); exit(1); }

    out('Sender wallet id=' . $wallet->id);

    $eth = new EthereumService();

    // Decrypt private key
    $priv = null;
    try {
        $priv = $wallet->getPrivateKey();
    } catch (\Throwable $e) {
        out('GET_PRIVATE_KEY_FAILED: ' . $e->getMessage());
        exit(2);
    }
    if (empty($priv)) { out('NO_PRIVATE_KEY_AVAILABLE'); exit(2); }

    out('Sender address: ' . $wallet->wallet_address);

    // Balance before
    try {
        $before = $eth->getBalance($wallet->wallet_address);
        out('Balance before (ETH): ' . $before);
    } catch (\Throwable $e) {
        out('GET_BALANCE_FAILED: ' . $e->getMessage());
        exit(3);
    }

    // Estimate gas
    try {
        $estimate = $eth->estimateGas($wallet->wallet_address, $to, $amount);
        out('Estimate result: ' . json_encode($estimate));
    } catch (\Throwable $e) {
        out('ESTIMATE_GAS_FAILED: ' . $e->getMessage());
        exit(4);
    }

    // Nonce
    try {
        $nonce = $eth->getTransactionCount($wallet->wallet_address, 'pending');
        out('Nonce (pending): ' . $nonce);
    } catch (\Throwable $e) {
        out('GET_TX_COUNT_FAILED: ' . $e->getMessage());
        exit(5);
    }

    // Send transaction
    try {
        out('Broadcasting sendTransaction...');
        $res = $eth->sendTransaction($priv, $to, $amount);
        out('sendTransaction response: ' . json_encode($res));
        $txHash = $res['txHash'] ?? ($res['hash'] ?? null);
        if (empty($txHash)) { out('NO_TX_HASH_RETURNED'); exit(6); }
        out('tx_hash: ' . $txHash);
    } catch (\Throwable $e) {
        out('SEND_TRANSACTION_FAILED: ' . $e->getMessage());
        exit(6);
    }

    // Poll receipt
    $receipt = null;
    $start = time();
    while (time() - $start < 120) {
        try {
            $r = $eth->getTransactionReceipt($txHash);
            if (!empty($r)) { $receipt = $r; break; }
        } catch (\Throwable $e) {
            out('GET_RECEIPT_ERROR: ' . $e->getMessage());
        }
        sleep(3);
    }

    if ($receipt === null) {
        out('RECEIPT_TIMEOUT');
        exit(7);
    }

    out('Receipt: ' . json_encode($receipt));
    $status = $eth->normalizeReceiptStatus($receipt['status'] ?? null);
    out('Normalized status: ' . var_export($status, true));

    // Balance after
    try {
        $after = $eth->getBalance($wallet->wallet_address);
        out('Balance after (ETH): ' . $after);
    } catch (\Throwable $e) {
        out('GET_BALANCE_AFTER_FAILED: ' . $e->getMessage());
    }

    out('DONE');
} catch (\Throwable $e) {
    out('EXCEPTION: ' . $e->getMessage());
    out($e->getTraceAsString());
    exit(99);
}
