<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;

$w = Wallet::find(186);
if (!$w) {
    echo json_encode(['error' => 'NOT_FOUND']);
    exit(0);
}

echo json_encode([
    'id' => $w->id,
    'wallet_address' => $w->wallet_address,
    'currency' => $w->currency,
    'network' => $w->network,
    'balance' => $w->balance,
]);
