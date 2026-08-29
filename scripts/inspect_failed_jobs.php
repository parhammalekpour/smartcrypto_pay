<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$f = DB::table('failed_jobs')->orderBy('id','desc')->take(10)->get();
echo json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
