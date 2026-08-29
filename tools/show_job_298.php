<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jobs = DB::table('jobs')->where('payload','like','%"transactionId";i:298%')->orWhere('payload','like','%"transactionId":298%')->get();
if ($jobs->isEmpty()) {
    echo json_encode(['found'=>false]) . PHP_EOL;
    exit(0);
}
$out = [];
foreach ($jobs as $j) {
    $o = (array)$j;
    $o['payload_snippet'] = substr($o['payload'],0,300);
    $out[] = $o;
}
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
