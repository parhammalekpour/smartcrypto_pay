<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$ids = [3809,3772,3767];
$out = [];
foreach ($ids as $id) {
    $r = DB::table('jobs')->where('id',$id)->first();
    $out[] = $r ? ['id'=>$r->id,'queue'=>$r->queue,'payload'=>$r->payload,'attempts'=>$r->attempts,'created_at'=>$r->created_at] : ['id'=>$id,'found'=>false];
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;