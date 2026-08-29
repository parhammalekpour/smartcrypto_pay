<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('jobs')->where('payload','like','%SendCryptoTransaction%')->orderBy('id','desc')->limit(20)->get();
$out = [];
foreach ($rows as $r) {
    $out[] = ['id'=>$r->id,'queue'=>$r->queue,'payload_preview'=>substr($r->payload,0,500),'created_at'=>($r->created_at??null)];
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
