<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;
use App\Services\BalanceSyncService;

$wallet = Wallet::find(210);
if (!$wallet) { echo json_encode(['error'=>'wallet_not_found']) . PHP_EOL; exit(1); }
$before = (string)$wallet->balance;
$service = new BalanceSyncService();
$result = $service->syncWallet($wallet);
$wallet->refresh();
$after = (string)$wallet->balance;
$out = [
    'wallet_id' => $wallet->id,
    'balance_before' => $before,
    'balance_after' => $after,
    'sync_result' => $result,
];
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
