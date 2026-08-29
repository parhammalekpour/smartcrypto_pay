<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$rows = DB::table('jobs')->orderBy('id','desc')->take(50)->get();
$out = [];
foreach ($rows as $r) {
    if (strpos($r->payload, 'SendCryptoTransaction') !== false) {
        $o = (array)$r;
        $o['payload_snippet'] = substr($o['payload'],0,500);
        $out[] = $o;
    }
}
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
