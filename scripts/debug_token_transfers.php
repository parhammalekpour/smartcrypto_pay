<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\EthereumService;

$eth = new EthereumService();
$contract = env('USDT_CONTRACT_ADDRESS');
$to = '0x6AB6f22AfCca3b4AEdc26E834815d47cca590Fcd';
$limit = 50;
$fromBlock = 11513832;
try {
    $res = $eth->getTokenTransfers($contract, $to, $limit, $fromBlock);
    echo json_encode(['transfers' => $res], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT) . PHP_EOL;
}
exit(0);
