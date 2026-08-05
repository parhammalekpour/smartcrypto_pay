<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoPrice
{
    protected $cacheDuration;

    public function __construct()
    {
        // Allow configuring cache duration via env for rapid development/testing
        // Default 5s: fast enough for a live-feel ticker while remaining within benign API rate usage.
        $this->cacheDuration = (int) env('CRYPTO_PRICE_CACHE', 5); // seconds
    }

    public function getPrice($crypto)
    {
        $symbol = $this->getSymbol($crypto);
        $cached = $this->readCache($symbol);

        if ($cached !== null && (time() - $cached['timestamp']) < $this->cacheDuration) {
            return $cached['price'];
        }

        $price = $this->fetchPrice($symbol);
        $this->writeCache($symbol, $price);

        return $price;
    }

    protected function fetchPrice($symbol)
    {
        // Only TradingView is used as the market data source.
        $price = $this->getTradingViewPrice($symbol);
        if ($price > 0) {
            Log::info('Price source for ' . $symbol . ': tradingview => ' . $price);
            return $price;
        }

        Log::warning('TradingView price fetch failed for ' . $symbol . ', returning 0');
        return 0;
    }

    protected function getCachePath()
    {
        return storage_path('app/crypto_price_cache.json');
    }

    protected function readCache($symbol)
    {
        $path = $this->getCachePath();
        if (! file_exists($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data[$symbol])) {
            return null;
        }

        return [
            'price' => (float) ($data[$symbol]['price'] ?? 0),
            'timestamp' => (int) ($data[$symbol]['timestamp'] ?? 0),
        ];
    }

    protected function writeCache($symbol, $price)
    {
        $path = $this->getCachePath();
        $data = [];

        if (file_exists($path)) {
            $json = file_get_contents($path);
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }

        $data[$symbol] = [
            'price' => $price,
            'timestamp' => time(),
        ];

        @file_put_contents($path, json_encode($data));
    }



    protected function getTradingViewPrice($symbol)
    {
        $symbolMap = [
            'BTC' => 'BINANCE:BTCUSDT',
            'ETH' => 'BINANCE:ETHUSDT',
            'USDT' => 'BINANCE:USDTUSDT',
        ];

        $tradingViewSymbol = $symbolMap[$symbol] ?? null;
        if (! $tradingViewSymbol) {
            return 0;
        }

        $url = env('TRADINGVIEW_API_BASE_URL', 'https://scanner.tradingview.com/crypto/scan');
        Log::info('Fetching price from TradingView: ' . $url . ' symbol=' . $tradingViewSymbol);

        try {
            $response = Http::timeout(10)->post($url, [
                'symbols' => [
                    'tickers' => [$tradingViewSymbol],
                ],
                'columns' => ['close'],
            ]);

            Log::info('TradingView Response Status: ' . $response->status());
            Log::debug('TradingView Response Body: ' . substr($response->body(), 0, 1000));

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['data'][0]['d'][0])) {
                    return (float) $data['data'][0]['d'][0];
                }
            }
        } catch (\Exception $e) {
            Log::error('TradingView API Error: ' . $e->getMessage());
        }

        return 0;
    }

    protected function getSymbol($crypto)
    {
        $symbolMap = [
            'bitcoin' => 'BTC',
            'ethereum' => 'ETH',
            'tether' => 'USDT',
            'BTC' => 'BTC',
            'ETH' => 'ETH',
            'USDT' => 'USDT',
            'USD' => 'USDT'
        ];

        return $symbolMap[strtolower($crypto)] ?? strtoupper($crypto);
    }

    public function convertToUSD($amount, $currency)
    {
        if ($currency === 'USD' || $currency === 'USDT') {
            return $amount;
        }

        $price = $this->getPrice($currency);
        return $amount * $price;
    }

}
