<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$job = app(App\Jobs\UpdateDepositConfirmationsJob::class);
$eth = app(App\Services\EthereumService::class);
$balance = app(App\Services\BalanceSyncService::class);

$job->handle($eth, $balance);

echo "UpdateDepositConfirmationsJob run\n";