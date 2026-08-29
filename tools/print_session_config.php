<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo json_encode([
    'driver' => config('session.driver'),
    'cookie' => config('session.cookie'),
    'path' => config('session.path'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'same_site' => config('session.same_site'),
    'lifetime' => config('session.lifetime'),
    'encrypt' => config('session.encrypt'),
], JSON_PRETTY_PRINT) . PHP_EOL;