<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if ($argc < 4) {
    echo json_encode(['error' => 'Usage: php check_eth_send_possible.php <from> <to> <amountEth>']) . PHP_EOL;
    exit(2);
}
$from = $argv[1];
$to = $argv[2];
$amount = $argv[3];

$eth = new App\Services\EthereumService();
try {
    $onchainBalance = trim((string)$eth->getBalance($from));
    $diagnose = $eth->estimateGas($from, $to, $amount);
    $gasLimit = isset($diagnose['estimate']['gasLimit']) ? (string)$diagnose['estimate']['gasLimit'] : ($diagnose['gasLimit'] ?? null);
    $gasPrice = isset($diagnose['gasPrice']) ? (string)$diagnose['gasPrice'] : null;
    if ($gasLimit === null) {
        echo json_encode(['error' => 'Unable to estimate gas', 'diagnose' => $diagnose]) . PHP_EOL;
        exit(3);
    }
    if ($gasPrice === null) {
        $gasPrice = (string)$eth->getGasPrice();
    }

    $gasCostWei = bcmul($gasLimit, $gasPrice, 0);
    $gasCostEth = bcdiv((string)$gasCostWei, '1000000000000000000', 18);

    $canSend = bccomp($onchainBalance, bcadd($amount, $gasCostEth, 18), 18) >= 0;

    echo json_encode(['from' => $from, 'to' => $to, 'amount' => $amount, 'onchainBalance' => $onchainBalance, 'gasLimit' => $gasLimit, 'gasPrice' => $gasPrice, 'gasCostEth' => $gasCostEth, 'canSend' => $canSend]) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]) . PHP_EOL;
    exit(1);
}
