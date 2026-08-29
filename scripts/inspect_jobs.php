<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jobs = DB::table('jobs')->where('queue','blockchain')->orderBy('id','desc')->take(20)->get();
$out = [];
foreach ($jobs as $j) {
    $payload = json_decode($j->payload, true);
    $out[] = ['id'=>$j->id,'queue'=>$j->queue,'attempts'=>$j->attempts,'reserved_at'=>$j->reserved_at,'available_at'=>$j->available_at,'created_at'=>$j->created_at,'payload_class'=> $payload['displayName'] ?? ($payload['data']['commandName'] ?? null)];
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
