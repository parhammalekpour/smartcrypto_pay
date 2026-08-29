<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('jobs')->where('payload','like','%SendCryptoTransaction%')->get();
$out = [];
foreach($rows as $r){ $out[] = ['id'=>$r->id,'payload'=>substr($r->payload,0,400)]; }

if(empty($out)) echo "NO_SEND_JOBS_FOUND\n";
else echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
