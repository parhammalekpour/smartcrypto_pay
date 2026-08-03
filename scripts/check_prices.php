<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Resolve the service
/** @var \App\Services\CryptoPrice $cp */
$cp = $app->make(\App\Services\CryptoPrice::class);

echo "Checking prices via CryptoPrice service...\n";

$btc = $cp->getPrice('BTC');
$eth = $cp->getPrice('ETH');

echo "BTC: "; var_export($btc); echo "\n";
echo "ETH: "; var_export($eth); echo "\n";

// Also call CoinGecko method directly if available via reflection
if (method_exists($cp, 'getCoinGeckoPrice')) {
    $ref = new ReflectionMethod($cp, 'getCoinGeckoPrice');
    $ref->setAccessible(true);
    echo "CoinGecko BTC: "; var_export($ref->invoke($cp, 'BTC')); echo "\n";
    echo "CoinGecko ETH: "; var_export($ref->invoke($cp, 'ETH')); echo "\n";
}

// Print last log lines
$log = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($log)) {
    echo "\n---- LAST LOG LINES ----\n";
    $lines = array_slice(file($log), -200);
    echo implode("", $lines);
} else {
    echo "No laravel.log found\n";
}

