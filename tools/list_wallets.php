<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\Wallet::select('id','currency','wallet_address','balance')->get() as $w) {
    echo json_encode($w) . PHP_EOL;
}
