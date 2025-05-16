<?php

namespace App\Services;

use App\Models\Instrument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PriceService
{
    protected $apiBaseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiBaseUrl = config('services.price_api.url');
        $this->apiKey = config('services.price_api.key');
    }

    /**
     * Belirli bir enstrüman için güncel fiyatı al
     */
    public function getCurrentPrice(string $symbol, string $type = 'both')
    {
        $cacheKey = "price_{$symbol}";
        $cachedPrice = Cache::get($cacheKey);

        // Önbellekte varsa ve 5 saniyeden yeni ise kullan
        if ($cachedPrice && $cachedPrice['timestamp'] > (time() - 5)) {
            return $type === 'both' ? $cachedPrice : $cachedPrice[$type];
        }

        try {
            // Gerçek API çağrısı - uygulamanızın entegre olduğu API'ye göre değişir
            $response = $this->fetchPriceFromApi($symbol);

            if (!$response) {
                // Demo verisi kullan
                $price = $this->getDemoPrice($symbol);
            } else {
                $price = [
                    'bid' => $response['bid'] ?? 0,
                    'ask' => $response['ask'] ?? 0,
                    'timestamp' => time()
                ];
            }

            // Önbelleğe al
            Cache::put($cacheKey, $price, now()->addMinutes(1));

            return $type === 'both' ? $price : $price[$type];
        } catch (\Exception $e) {
            Log::error("Fiyat alınamadı: {$symbol} - " . $e->getMessage());

            // Demo verisi döndür
            $price = $this->getDemoPrice($symbol);
            return $type === 'both' ? $price : $price[$type];
        }
    }

    /**
     * Birden fazla enstrüman için güncel fiyatları al
     */
    public function getCurrentPrices(array $symbols)
    {
        $prices = [];

        foreach ($symbols as $symbol) {
            $prices[$symbol] = $this->getCurrentPrice($symbol);
        }

        return $prices;
    }

    /**
     * OHLC veri al (Grafik için)
     */
    public function getOHLCData(string $symbol, string $timeframe, int $from, int $to)
    {
        try {
            // Gerçek API çağrısı - uygulamanızın entegre olduğu API'ye göre değişir
            $response = $this->fetchOHLCFromApi($symbol, $timeframe, $from, $to);

            if (!$response) {
                // Demo verisi kullan
                return $this->getDemoOHLCData($symbol, $timeframe, $from, $to);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("OHLC verisi alınamadı: {$symbol} - " . $e->getMessage());

            // Demo verisi döndür
            return $this->getDemoOHLCData($symbol, $timeframe, $from, $to);
        }
    }

    /**
     * API'den fiyat verisi çek
     */
    protected function fetchPriceFromApi(string $symbol)
    {
        // NOT: Bu fonksiyon, uygulamanızın entegre olduğu API'ye göre değişecektir
        // Örnek olarak Alpha Vantage API kullanım şekli:

        try {
            $response = Http::get($this->apiBaseUrl, [
                'function' => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => substr($symbol, 0, 3),
                'to_currency' => substr($symbol, 3, 3),
                'apikey' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['Realtime Currency Exchange Rate'])) {
                    $exchangeRate = $data['Realtime Currency Exchange Rate']['5. Exchange Rate'];
                    // Spread eklemek için küçük bir değer ekle/çıkar
                    $spread = $exchangeRate * 0.0002; // %0.02 spread

                    return [
                        'bid' => floatval($exchangeRate) - $spread/2,
                        'ask' => floatval($exchangeRate) + $spread/2,
                        'timestamp' => time()
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("API'den fiyat alınamadı: " . $e->getMessage());
            return null;
        }
    }

    /**
     * API'den OHLC verisi çek
     */
    protected function fetchOHLCFromApi(string $symbol, string $timeframe, int $from, int $to)
    {
        // NOT: Bu fonksiyon, uygulamanızın entegre olduğu API'ye göre değişecektir
        // Örnek olarak Alpha Vantage API kullanım şekli:

        try {
            $function = 'FX_INTRADAY';
            $interval = $this->mapTimeframeToInterval($timeframe);

            $response = Http::get($this->apiBaseUrl, [
                'function' => $function,
                'from_symbol' => substr($symbol, 0, 3),
                'to_symbol' => substr($symbol, 3, 3),
                'interval' => $interval,
                'outputsize' => 'full',
                'apikey' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $timeSeriesKey = "Time Series FX ({$interval})";

                if (isset($data[$timeSeriesKey])) {
                    $result = [];
                    foreach ($data[$timeSeriesKey] as $datetime => $values) {
                        $timestamp = strtotime($datetime);

                        if ($timestamp >= $from && $timestamp <= $to) {
                            $result[] = [
                                'time' => $timestamp * 1000, // milisaniye cinsinden
                                'open' => floatval($values['1. open']),
                                'high' => floatval($values['2. high']),
                                'low' => floatval($values['3. low']),
                                'close' => floatval($values['4. close']),
                                'volume' => floatval($values['5. volume'])
                            ];
                        }
                    }

                    return $result;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("API'den OHLC verisi alınamadı: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Timeframe'i API intervaline dönüştür
     */
    protected function mapTimeframeToInterval(string $timeframe)
    {
        $map = [
            '1m' => '1min',
            '5m' => '5min',
            '15m' => '15min',
            '30m' => '30min',
            '1h' => '60min',
            '4h' => '240min', // Alpha Vantage'da yok, en yakını seçildi
            '1d' => 'daily',
        ];

        return $map[$timeframe] ?? '60min'; // Varsayılan 1 saat
    }

    /**
     * Demo fiyat verisi oluştur
     */
    protected function getDemoPrice(string $symbol)
    {
        // Sabit bir baz değer (gerçek para birimi çiftlerine benzer)
        $baseValues = [
            'EURUSD' => 1.09,
            'GBPUSD' => 1.29,
            'USDJPY' => 108.7,
            'USDCHF' => 0.91,
            'AUDUSD' => 0.68,
            'NZDUSD' => 0.63,
            'BTCUSD' => 62000,
            'ETHUSD' => 3500,
            // Diğer semboller de eklenebilir
        ];

        $base = $baseValues[$symbol] ?? 1.0;

        // Küçük rastgele değişiklik ekle (%0.2 maksimum)
        $randomChange = $base * (mt_rand(-20, 20) / 10000);
        $currentPrice = $base + $randomChange;

        // Spread ekle (%0.02 - değişebilir)
        $spread = $currentPrice * 0.0002;

        return [
            'bid' => round($currentPrice - $spread/2, 5),
            'ask' => round($currentPrice + $spread/2, 5),
            'timestamp' => time()
        ];
    }

    /**
     * Demo OHLC verisi oluştur
     */
    protected function getDemoOHLCData(string $symbol, string $timeframe, int $from, int $to)
    {
        $result = [];

        // Sabit bir baz değer
        $baseValues = [
            'EURUSD' => 1.09,
            'GBPUSD' => 1.29,
            'USDJPY' => 108.7,
            'USDCHF' => 0.91,
            'AUDUSD' => 0.68,
            'NZDUSD' => 0.63,
            'BTCUSD' => 62000,
            'ETHUSD' => 3500,
            // Diğer semboller de eklenebilir
        ];

        $base = $baseValues[$symbol] ?? 1.0;

        // Timeframe'e göre zaman aralığını ayarla
        $interval = $this->getIntervalSeconds($timeframe);

        // Verinin volatilitesi
        $volatility = $base * 0.001; // Baz değerin %0.1'i

        if (strpos($symbol, 'BTC') !== false || strpos($symbol, 'ETH') !== false) {
            $volatility = $base * 0.005; // Kripto için %0.5
        }

        $currentTime = $from;
        $currentPrice = $base;

        while ($currentTime <= $to) {
            $randomChange = $volatility * (mt_rand(-100, 100) / 100);

            $open = $currentPrice;
            $close = $open + $randomChange;

            // High ve low değerlerini open ve close arasında belirle
            $high = max($open, $close) + abs($randomChange) * (mt_rand(10, 50) / 100);
            $low = min($open, $close) - abs($randomChange) * (mt_rand(10, 50) / 100);

            $result[] = [
                'time' => $currentTime * 1000, // milisaniye cinsinden
                'open' => round($open, 5),
                'high' => round($high, 5),
                'low' => round($low, 5),
                'close' => round($close, 5),
                'volume' => mt_rand(100, 1000)
            ];

            $currentPrice = $close;
            $currentTime += $interval;
        }

        return $result;
    }

    /**
     * Timeframe'i saniye cinsinden aralığa dönüştür
     */
    protected function getIntervalSeconds(string $timeframe)
    {
        $map = [
            '1m' => 60,
            '5m' => 300,
            '15m' => 900,
            '30m' => 1800,
            '1h' => 3600,
            '4h' => 14400,
            '1d' => 86400,
        ];

        return $map[$timeframe] ?? 3600; // Varsayılan 1 saat
    }
}
