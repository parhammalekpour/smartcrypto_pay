<?php
require __DIR__ . '/../vendor/autoload.php';
$app=require __DIR__ . '/../bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \DB::table('failed_jobs')->where('payload','like','%"transactionId";i:298%')->orWhere('payload','like','%"transactionId":298%')->get();
echo json_encode($rows, JSON_PRETTY_PRINT) . PHP_EOL;
