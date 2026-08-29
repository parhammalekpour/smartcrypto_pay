<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$txIds = [300,301,302];
$out = [];
foreach ($txIds as $tid) {
    $rows = DB::table('jobs')->where('payload','like','%"transactionId";i:$tid%')->orWhere('payload','like','%"transactionId";i:{$tid}%')->get();
    $out[$tid] = $rows->map(function($r){ return ['id'=>$r->id,'queue'=>$r->queue,'payload_preview'=>substr($r->payload,0,300),'created_at'=>$r->created_at]; })->toArray();
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;