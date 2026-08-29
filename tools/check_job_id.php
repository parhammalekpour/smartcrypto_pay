<?php
require __DIR__ . '/../vendor/autoload.php';
$app=require __DIR__ . '/../bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$j=\DB::table('jobs')->where('id',3592)->first();
echo json_encode($j,JSON_PRETTY_PRINT) . PHP_EOL;
