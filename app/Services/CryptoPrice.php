<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CryptoPrice
{
    protected $binanceUrl = 'https://api.binance.com/api/v3/ticker/price';
    protected $cacheDuration = 60; // 1 minute for real-time prices

    public function getPrice($crypto)
    {
        $symbol = $this->getSymbol($crypto);
        $cacheKey = 'binance_price_' . strtolower($symbol);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($symbol) {
            try {
                $url = $this->binanceUrl . '?symbol=' . $symbol . 'USDT';
                Log::info('Fetching price from Binance: ' . $url);
                
                $response = Http::timeout(10)->get($url);

                Log::info('Binance Response Status: ' . $response->status());
                Log::info('Binance Response: ' . $response->body());

                if ($response->successful()) {
                    $data = $response->json();
                    $price = (float) ($data['price'] ?? 0);
                    Log::info('Binance Price for ' . $symbol . ': ' . $price);
                    
                    if ($price > 0) {
                        return $price;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Binance API Error: ' . $e->getMessage());
            }

            $fallback = $this->getFallbackPrice($symbol);
            Log::info('Using fallback price for ' . $symbol . ': ' . $fallback);
            
            return $fallback;
        });
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

    protected function getFallbackPrice($symbol)
    {
        // Current market prices as fallback
        $fallback = [
            'BTC' => 67000,
            'ETH' => 3500,
            'USDT' => 1,
            'USD' => 1
        ];

        return (float) ($fallback[$symbol] ?? 0);
    }
}
