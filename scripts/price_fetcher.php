<?php
// Simple long-running fetcher that uses the app's CryptoPrice service
// Run with: php scripts/price_fetcher.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var \App\Services\CryptoPrice $cp */
$cp = $app->make(\App\Services\CryptoPrice::class);

$storage = __DIR__ . '/../storage/app/crypto_prices.json';

echo "Starting price fetcher (Ctrl+C to stop)\n";

while (true) {
    try {
        $btc = $cp->getPrice('BTC');
        $eth = $cp->getPrice('ETH');

        $data = [
            'btc' => $btc,
            'eth' => $eth,
            'usd' => 1,
            'timestamp' => time()
        ];

        file_put_contents($storage, json_encode($data));
        echo "Fetched at " . date('c') . " BTC={$btc} ETH={$eth}\n";
    } catch (\Throwable $e) {
        echo "Fetcher error: " . $e->getMessage() . "\n";
    }

    // sleep for configured interval (seconds); use env or default 5
    $interval = (int) getenv('CRYPTO_PRICE_FETCH_INTERVAL') ?: 5;
    sleep(max(2, $interval));
}
