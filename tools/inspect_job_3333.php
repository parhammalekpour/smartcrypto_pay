<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$r = DB::table('jobs')->where('id',3333)->first();
if(!$r){ echo "JOB_NOT_FOUND\n"; exit(1);} echo json_encode($r, JSON_PRETTY_PRINT) . PHP_EOL;