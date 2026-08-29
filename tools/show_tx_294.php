<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$t = Transaction::find(294);
if (!$t) { echo "TX_NOT_FOUND\n"; exit(1); }
echo json_encode($t->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
