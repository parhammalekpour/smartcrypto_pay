<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\SendCryptoTransaction;
use Illuminate\Support\Facades\Log;

$job = new SendCryptoTransaction(298);
try {
    $job->handle();
    echo "Job handle completed\n";
} catch (\Throwable $e) {
    echo "Job handle threw: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
