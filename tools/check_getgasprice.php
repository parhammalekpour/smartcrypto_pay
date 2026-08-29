<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$eth = new App\Services\EthereumService();
try {
    $fee = $eth->getFeeData();
    $gasPrice = $eth->getGasPrice();
    echo json_encode(['feeData' => $fee, 'computedGasPrice' => $gasPrice]) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]) . PHP_EOL;
    exit(1);
}
