<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$rows = DB::table('failed_jobs')->orderBy('id','desc')->limit(50)->get();
$out = [];
foreach ($rows as $r) {
    $out[] = ['id'=>$r->id,'failed_at'=>$r->failed_at,'exception'=>substr($r->exception,0,400),'payload_preview'=>substr($r->payload,0,400)];
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;