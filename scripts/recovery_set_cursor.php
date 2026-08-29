<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if ($argc < 2) { echo json_encode(['error'=>'usage']) . PHP_EOL; exit(1); }
$new = (int)$argv[1];
$w = App\Models\Wallet::find(186);
if (!$w) { echo json_encode(['error'=>'WALLET_NOT_FOUND']) . PHP_EOL; exit(1); }
$old = $w->last_scanned_block;
$w->last_scanned_block = $new;
$w->save();
echo json_encode(['old'=>$old,'new'=>$new]) . PHP_EOL;
