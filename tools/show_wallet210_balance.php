<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$b = DB::table('wallets')->where('id',210)->first();
echo json_encode(['id'=>$b->id,'balance'=>$b->balance,'updated_at'=>$b->updated_at], JSON_PRETTY_PRINT) . PHP_EOL;