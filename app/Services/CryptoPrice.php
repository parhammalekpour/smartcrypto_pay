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
        // 1) Primary: TradingView
        $price = $this->getTradingViewPrice($symbol);
        if ($price > 0) {
            Log::info('Price source for ' . $symbol . ': tradingview => ' . $price);
            return $price;
        }

        // 2) Primary fallback: Bitpin
        $price = $this->getBitpinPrice($symbol);
        if ($price > 0) {
            Log::info('Price source for ' . $symbol . ': bitpin => ' . $price);
            return $price;
        }

        // 3) Secondary fallback: CoinGecko
        $price = $this->getCoinGeckoPrice($symbol);
        if ($price > 0) {
            Log::info('Price source for ' . $symbol . ': coingecko => ' . $price);
            return $price;
        }

        Log::warning('All price sources failed for ' . $symbol . ', returning 0');
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


    protected function getBitpinPrice($symbol)
    {
        $bitpinSymbol = $this->getBitpinSymbol($symbol);
        if (! $bitpinSymbol) {
            return 0;
        }

        $baseUrl = env('BITPIN_API_BASE_URL', 'https://api.bitpin.ir');
        $url = rtrim($baseUrl, '/') . '/api/v1/mkt/tickers/';
        Log::info('Fetching price from Bitpin: ' . $url . ' symbol=' . $bitpinSymbol);

        try {
            $response = Http::timeout(10)->get($url);
            Log::info('Bitpin Response Status: ' . $response->status());
            // Log a snippet of body to avoid very large logs
            Log::debug('Bitpin Response (snippet): ' . substr($response->body(), 0, 1000));

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['value'] ?? $data;
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (is_array($item) && isset($item['symbol']) && $item['symbol'] === $bitpinSymbol) {
                            return (float) ($item['price'] ?? 0);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Bitpin API Error: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Query CoinGecko as a fallback source (no API key required).
     * Returns USD price for supported symbols (BTC, ETH) or 0 on failure.
     */
    protected function getCoinGeckoPrice($symbol)
    {
        $map = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'USDT' => 'tether'
        ];

        $id = $map[$symbol] ?? null;
        if (! $id) {
            return 0;
        }

        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=' . $id . '&vs_currencies=usd';
        Log::info('Fetching price from CoinGecko: ' . $url);

        try {
            $response = Http::timeout(10)->get($url);
            Log::info('CoinGecko Response Status: ' . $response->status());
            Log::debug('CoinGecko Response Body: ' . substr($response->body(), 0, 1000));

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data[$id]['usd'])) {
                    return (float) $data[$id]['usd'];
                }
            }
        } catch (\Exception $e) {
            Log::error('CoinGecko API Error: ' . $e->getMessage());
        }

        return 0;
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

    protected function getBitpinSymbol($symbol)
    {
        $symbolMap = [
            'BTC' => 'BTC_USDT',
            'ETH' => 'ETH_USDT',
            'USDT' => 'USDT_USDT',
        ];

        return $symbolMap[$symbol] ?? null;
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
