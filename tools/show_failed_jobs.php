<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failed = \DB::table('failed_jobs')->get();
echo json_encode($failed, JSON_PRETTY_PRINT) . PHP_EOL;
