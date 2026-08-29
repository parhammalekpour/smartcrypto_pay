<?php
// Boot Laravel application and list wallets
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Wallet;

$wallets = Wallet::all();
foreach ($wallets as $w) {
    echo implode('|', [
        $w->id,
        $w->wallet_address,
        $w->currency,
        (string)$w->balance,
        $w->owner_type,
        $w->owner_id
    ]) . PHP_EOL;
}
