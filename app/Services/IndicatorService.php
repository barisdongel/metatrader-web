<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IndicatorService
{
    /**
     * İstenen teknik göstergeyi hesapla
     */
    public function calculate(string $indicator, array $chartData, array $params = [])
    {
        switch ($indicator) {
            case 'sma':
                return $this->calculateSMA($chartData, $params);
            case 'ema':
                return $this->calculateEMA($chartData, $params);
            case 'bollinger':
                return $this->calculateBollingerBands($chartData, $params);
            case 'macd':
                return $this->calculateMACD($chartData, $params);
            case 'rsi':
                return $this->calculateRSI($chartData, $params);
            default:
                throw new \Exception("Desteklenmeyen gösterge: {$indicator}");
        }
    }

    /**
     * Basit Hareketli Ortalama (SMA) hesapla
     */
    protected function calculateSMA(array $chartData, array $params)
    {
        $period = $params['period'] ?? 14;

        if (count($chartData) < $period) {
            throw new \Exception("SMA hesaplamak için yeterli veri yok.");
        }

        $result = [];

        for ($i = $period - 1; $i < count($chartData); $i++) {
            $sum = 0;

            for ($j = 0; $j < $period; $j++) {
                $sum += $chartData[$i - $j]['close'];
            }

            $avg = $sum / $period;

            $result[] = [
                'time' => $chartData[$i]['time'],
                'value' => round($avg, 5)
            ];
        }

        return $result;
    }

    /**
     * Üstel Hareketli Ortalama (EMA) hesapla
     */
    protected function calculateEMA(array $chartData, array $params)
    {
        $period = $params['period'] ?? 14;

        if (count($chartData) < $period) {
            throw new \Exception("EMA hesaplamak için yeterli veri yok.");
        }

        $result = [];
        $multiplier = 2 / ($period + 1);

        // İlk EMA = İlk SMA
        $sum = 0;
        for ($i = 0; $i < $period; $i++) {
            $sum += $chartData[$i]['close'];
        }
        $ema = $sum / $period;

        $result[] = [
            'time' => $chartData[$period - 1]['time'],
            'value' => round($ema, 5)
        ];

        // Sonraki EMA değerleri
        for ($i = $period; $i < count($chartData); $i++) {
            $ema = ($chartData[$i]['close'] - $ema) * $multiplier + $ema;

            $result[] = [
                'time' => $chartData[$i]['time'],
                'value' => round($ema, 5)
            ];
        }

        return $result;
    }

    /**
     * Bollinger Bantları hesapla
     */
    protected function calculateBollingerBands(array $chartData, array $params)
    {
        $period = $params['period'] ?? 20;
        $deviations = $params['deviations'] ?? 2;

        if (count($chartData) < $period) {
            throw new \Exception("Bollinger Bantları hesaplamak için yeterli veri yok.");
        }

        $result = [];

        for ($i = $period - 1; $i < count($chartData); $i++) {
            $sum = 0;
            $prices = [];

            for ($j = 0; $j < $period; $j++) {
                $price = $chartData[$i - $j]['close'];
                $sum += $price;
                $prices[] = $price;
            }

            $sma = $sum / $period;

            // Standart sapma hesapla
            $sumSquares = 0;
            foreach ($prices as $price) {
                $sumSquares += pow($price - $sma, 2);
            }

            $std = sqrt($sumSquares / $period);

            $result[] = [
                'time' => $chartData[$i]['time'],
                'middle' => round($sma, 5),
                'upper' => round($sma + ($deviations * $std), 5),
                'lower' => round($sma - ($deviations * $std), 5)
            ];
        }

        return $result;
    }

    /**
     * MACD (Moving Average Convergence Divergence) hesapla
     */
    protected function calculateMACD(array $chartData, array $params)
    {
        $fastPeriod = $params['fast_period'] ?? 12;
        $slowPeriod = $params['slow_period'] ?? 26;
        $signalPeriod = $params['signal_period'] ?? 9;

        if (count($chartData) < $slowPeriod + $signalPeriod) {
            throw new \Exception("MACD hesaplamak için yeterli veri yok.");
        }

        // Hızlı EMA hesapla
        $fastEMAParams = ['period' => $fastPeriod];
        $fastEMA = $this->calculateEMA($chartData, $fastEMAParams);

        // Yavaş EMA hesapla
        $slowEMAParams = ['period' => $slowPeriod];
        $slowEMA = $this->calculateEMA($chartData, $slowEMAParams);

        // MACD hattı = Hızlı EMA - Yavaş EMA
        $macdLine = [];
        $macdChartData = [];

        $startIdx = count($chartData) - count($slowEMA);

        for ($i = 0; $i < count($slowEMA); $i++) {
            $fastIndex = $i + (count($fastEMA) - count($slowEMA));

            if ($fastIndex >= 0) {
                $macd = $fastEMA[$fastIndex]['value'] - $slowEMA[$i]['value'];

                $macdLine[] = [
                    'time' => $slowEMA[$i]['time'],
                    'value' => $macd
                ];

                $macdChartData[] = [
                    'time' => $slowEMA[$i]['time'],
                    'close' => $macd
                ];
            }
        }

        // Sinyal hattı = MACD hattının EMA'sı
        $signalParams = ['period' => $signalPeriod];
        $signalLine = $this->calculateEMA($macdChartData, $signalParams);

        // Histogram = MACD hattı - Sinyal hattı
        $histogram = [];

        $startIdx = count($macdLine) - count($signalLine);

        for ($i = 0; $i < count($signalLine); $i++) {
            $macdIdx = $i + $startIdx;

            $histogram[] = [
                'time' => $signalLine[$i]['time'],
                'value' => $macdLine[$macdIdx]['value'] - $signalLine[$i]['value']
            ];
        }

        // Son dönem verilerini birleştir
        $result = [];

        for ($i = 0; $i < count($histogram); $i++) {
            $signalIdx = $i;
            $macdIdx = $i + $startIdx;

            $result[] = [
                'time' => $histogram[$i]['time'],
                'macd' => round($macdLine[$macdIdx]['value'], 5),
                'signal' => round($signalLine[$signalIdx]['value'], 5),
                'histogram' => round($histogram[$i]['value'], 5)
            ];
        }

        return $result;
    }

    /**
     * Göreceli Güç Endeksi (RSI) hesapla
     */
    protected function calculateRSI(array $chartData, array $params)
    {
        $period = $params['period'] ?? 14;

        if (count($chartData) < $period + 1) {
            throw new \Exception("RSI hesaplamak için yeterli veri yok.");
        }

        $result = [];
        $gains = [];
        $losses = [];

        // İlk fiyat değişimlerini hesapla
        for ($i = 1; $i < count($chartData); $i++) {
            $change = $chartData[$i]['close'] - $chartData[$i - 1]['close'];

            if ($change >= 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        // İlk ortalama kazanç ve kayıp hesapla
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // İlk RSI hesapla
        if ($avgLoss == 0) {
            $rsi = 100;
        } else {
            $rs = $avgGain / $avgLoss;
            $rsi = 100 - (100 / (1 + $rs));
        }

        $result[] = [
            'time' => $chartData[$period]['time'],
            'value' => round($rsi, 2)
        ];

        // Sonraki RSI değerlerini hesapla
        for ($i = $period + 1; $i < count($chartData); $i++) {
            $idx = $i - 1;

            // Smoothed/Wilder's RSI
            $avgGain = (($avgGain * ($period - 1)) + $gains[$idx - $period]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$idx - $period]) / $period;

            if ($avgLoss == 0) {
                $rsi = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $rsi = 100 - (100 / (1 + $rs));
            }

            $result[] = [
                'time' => $chartData[$i]['time'],
                'value' => round($rsi, 2)
            ];
        }

        return $result;
    }
}
