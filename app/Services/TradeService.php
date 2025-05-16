<?php

namespace App\Services;

use App\Models\User;
use App\Models\Trade;
use App\Models\Position;
use App\Models\Instrument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TradeService
{
    protected $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Yeni bir alım-satım emri yerleştir
     */
    public function placeOrder(array $data)
    {
        $user = User::findOrFail($data['user_id']);
        $instrument = Instrument::findOrFail($data['instrument_id']);

        // İşlem saatleri kontrolü
        if (!$instrument->is_active || !$instrument->isTradingHours()) {
            throw new \Exception("{$instrument->symbol} için şu anda işlem yapılamaz.");
        }

        // Lot büyüklüğü kontrolü
        if ($data['volume'] < $instrument->min_lot || $data['volume'] > $instrument->max_lot) {
            throw new \Exception("Lot büyüklüğü {$instrument->min_lot} ile {$instrument->max_lot} arasında olmalıdır.");
        }

        // İşlem türüne göre farklı işlemler
        if ($data['order_type'] === 'market') {
            return $this->processMarketOrder($user, $instrument, $data);
        } else {
            return $this->processPendingOrder($user, $instrument, $data);
        }
    }

    /**
     * Piyasa emrini işle (anında gerçekleşir)
     */
    protected function processMarketOrder(User $user, Instrument $instrument, array $data)
    {
        // Güncel fiyatı al
        $priceType = $data['direction'] === 'buy' ? 'ask' : 'bid';
        $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

        // Gerekli teminat hesapla
        $marginRequired = $this->calculateRequiredMargin($instrument, $data['volume'], $currentPrice);

        // Yeterli bakiye kontrolü
        if ($user->account_balance < $marginRequired) {
            throw new \Exception("Yetersiz bakiye. Gerekli teminat: {$marginRequired} {$user->account_currency}");
        }

        DB::beginTransaction();

        try {
            // Yeni pozisyon oluştur
            $position = new Position([
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'direction' => $data['direction'],
                'volume' => $data['volume'],
                'open_price' => $currentPrice,
                'current_price' => $currentPrice,
                'take_profit' => $data['take_profit'] ?? null,
                'stop_loss' => $data['stop_loss'] ?? null,
                'profit' => 0,
                'swap' => 0,
                'commission' => $this->calculateCommission($instrument, $data['volume'], $currentPrice),
                'open_time' => now(),
                'status' => 'open',
            ]);

            $position->save();

            // İşlem kaydı oluştur
            $trade = new Trade([
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'position_id' => $position->id,
                'direction' => $data['direction'],
                'open_price' => $currentPrice,
                'volume' => $data['volume'],
                'open_time' => now(),
                'take_profit' => $data['take_profit'] ?? null,
                'stop_loss' => $data['stop_loss'] ?? null,
                'profit' => 0,
                'commission' => $position->commission,
                'swap' => 0,
                'status' => 'open',
                'order_type' => 'market',
                'comment' => $data['comment'] ?? null,
            ]);

            $trade->save();

            DB::commit();

            return [
                'success' => true,
                'message' => "{$instrument->symbol} için {$data['direction']} pozisyonu açıldı",
                'position' => $position,
                'trade' => $trade,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("İşlem oluşturulamadı: " . $e->getMessage());
            throw new \Exception("İşlem gerçekleştirilemedi: " . $e->getMessage());
        }
    }

    /**
     * Bekleyen emri işle (limit, stop)
     */
    protected function processPendingOrder(User $user, Instrument $instrument, array $data)
    {
        // Fiyat gerekli
        if (!isset($data['price']) || $data['price'] <= 0) {
            throw new \Exception("Bekleyen emirler için fiyat belirtilmelidir.");
        }

        // Güncel fiyatı al ve kontrol et
        $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, 'both');
        $isValidPrice = $this->validatePendingOrderPrice(
            $data['order_type'],
            $data['direction'],
            $data['price'],
            $currentPrice
        );

        if (!$isValidPrice) {
            throw new \Exception("Geçersiz emir fiyatı. Lütfen fiyatı kontrol edin.");
        }

        // Gerekli teminat hesapla
        $marginRequired = $this->calculateRequiredMargin($instrument, $data['volume'], $data['price']);

        // Yeterli bakiye kontrolü
        if ($user->account_balance < $marginRequired) {
            throw new \Exception("Yetersiz bakiye. Gerekli teminat: {$marginRequired} {$user->account_currency}");
        }

        DB::beginTransaction();

        try {
            // İşlem kaydı oluştur
            $trade = new Trade([
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'direction' => $data['direction'],
                'open_price' => $data['price'],
                'volume' => $data['volume'],
                'open_time' => now(),
                'take_profit' => $data['take_profit'] ?? null,
                'stop_loss' => $data['stop_loss'] ?? null,
                'profit' => 0,
                'commission' => $this->calculateCommission($instrument, $data['volume'], $data['price']),
                'swap' => 0,
                'status' => 'pending',
                'order_type' => $data['order_type'],
                'comment' => $data['comment'] ?? null,
            ]);

            $trade->save();

            DB::commit();

            return [
                'success' => true,
                'message' => "{$instrument->symbol} için bekleyen {$data['direction']} emri oluşturuldu",
                'trade' => $trade,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bekleyen emir oluşturulamadı: " . $e->getMessage());
            throw new \Exception("Emir oluşturulamadı: " . $e->getMessage());
        }
    }

    /**
     * Bekleyen emir fiyatını doğrula
     */
    protected function validatePendingOrderPrice($orderType, $direction, $price, $currentPrice)
    {
        if ($orderType === 'limit') {
            // Limit almak için fiyat, piyasa fiyatından düşük olmalı
            if ($direction === 'buy' && $price >= $currentPrice['ask']) {
                return false;
            }
            // Limit satmak için fiyat, piyasa fiyatından yüksek olmalı
            if ($direction === 'sell' && $price <= $currentPrice['bid']) {
                return false;
            }
        } elseif ($orderType === 'stop') {
            // Stop almak için fiyat, piyasa fiyatından yüksek olmalı
            if ($direction === 'buy' && $price <= $currentPrice['ask']) {
                return false;
            }
            // Stop satmak için fiyat, piyasa fiyatından düşük olmalı
            if ($direction === 'sell' && $price >= $currentPrice['bid']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pozisyonu kapat
     */
    public function closePosition(Position $position)
    {
        if ($position->status !== 'open') {
            throw new \Exception("Bu pozisyon zaten kapalı.");
        }

        $instrument = $position->instrument;

        // Güncel fiyatı al
        $priceType = $position->direction === 'buy' ? 'bid' : 'ask';
        $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

        // Kar/zarar hesapla
        $profit = $this->calculatePositionProfit($position, $currentPrice);

        DB::beginTransaction();

        try {
            // Pozisyonu güncelle
            $position->current_price = $currentPrice;
            $position->profit = $profit;
            $position->status = 'closed';
            $position->save();

            // İlgili işlemleri güncelle
            foreach ($position->trades as $trade) {
                if ($trade->status === 'open') {
                    $trade->close_price = $currentPrice;
                    $trade->close_time = now();
                    $trade->profit = $profit;
                    $trade->status = 'closed';
                    $trade->save();
                }
            }

            // Kullanıcı bakiyesini güncelle
            $user = $position->user;
            $user->account_balance += $profit;
            $user->save();

            DB::commit();

            return [
                'success' => true,
                'message' => "{$instrument->symbol} pozisyonu kapatıldı",
                'position' => $position,
                'profit' => $profit,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Pozisyon kapatılamadı: " . $e->getMessage());
            throw new \Exception("Pozisyon kapatılamadı: " . $e->getMessage());
        }
    }

    /**
     * Pozisyon güncelle (SL/TP)
     */
    public function updatePosition(Position $position, array $data)
    {
        if ($position->status !== 'open') {
            throw new \Exception("Kapalı pozisyon güncellenemez.");
        }

        DB::beginTransaction();

        try {
            if (isset($data['stop_loss'])) {
                $position->stop_loss = $data['stop_loss'];
            }

            if (isset($data['take_profit'])) {
                $position->take_profit = $data['take_profit'];
            }

            $position->save();

            // İlgili açık işlemleri de güncelle
            foreach ($position->trades as $trade) {
                if ($trade->status === 'open') {
                    if (isset($data['stop_loss'])) {
                        $trade->stop_loss = $data['stop_loss'];
                    }

                    if (isset($data['take_profit'])) {
                        $trade->take_profit = $data['take_profit'];
                    }

                    $trade->save();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Pozisyon başarıyla güncellendi",
                'position' => $position,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Pozisyon güncellenemedi: " . $e->getMessage());
            throw new \Exception("Pozisyon güncellenemedi: " . $e->getMessage());
        }
    }

    /**
     * Pozisyon kârını hesapla
     */
    public function calculatePositionProfit(Position $position, $currentPrice)
    {
        $instrument = $position->instrument;

        $priceDiff = $position->direction === 'buy'
            ? $currentPrice - $position->open_price
            : $position->open_price - $currentPrice;

        $pipValue = $instrument->pip_value * $position->volume;
        $pipsCount = $priceDiff / $instrument->point;

        return round($pipsCount * $pipValue, 2);
    }

    /**
     * Gerekli teminat hesapla
     */
    public function calculateRequiredMargin(Instrument $instrument, $volume, $price)
    {
        // Örnek formül: Kontrat büyüklüğü * Lot * Fiyat * Margin oranı
        $contractValue = $instrument->contract_size * $volume * $price;
        return $contractValue * ($instrument->margin_required / 100);
    }

    /**
     * Komisyon hesapla
     */
    protected function calculateCommission(Instrument $instrument, $volume, $price)
    {
        // Örnek formül: Kontrat büyüklüğü * Lot * Fiyat * 0.001 (0.1%)
        $contractValue = $instrument->contract_size * $volume * $price;
        return $contractValue * 0.001;
    }

    /**
     * Tüm hesabın equity'sini hesapla
     */
    public function calculateEquity($userId)
    {
        $user = User::findOrFail($userId);
        $positions = Position::where('user_id', $userId)
            ->where('status', 'open')
            ->with('instrument')
            ->get();

        $openProfit = 0;

        foreach ($positions as $position) {
            $instrument = $position->instrument;
            $priceType = $position->direction === 'buy' ? 'bid' : 'ask';
            $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

            $openProfit += $this->calculatePositionProfit($position, $currentPrice);
        }

        return $user->account_balance + $openProfit;
    }

    /**
     * Kullanılan toplam teminatı hesapla
     */
    public function calculateMargin($userId)
    {
        $positions = Position::where('user_id', $userId)
            ->where('status', 'open')
            ->with('instrument')
            ->get();

        $totalMargin = 0;

        foreach ($positions as $position) {
            $instrument = $position->instrument;
            $totalMargin += $this->calculateRequiredMargin(
                $instrument,
                $position->volume,
                $position->open_price
            );
        }

        return $totalMargin;
    }

    /**
     * Teminat seviyesi hesapla
     */
    public function calculateMarginLevel($userId)
    {
        $equity = $this->calculateEquity($userId);
        $margin = $this->calculateMargin($userId);

        if ($margin <= 0) {
            return 0;
        }

        return ($equity / $margin) * 100;
    }

    /**
     * Bugünün kâr/zararını hesapla
     */
    public function calculateProfitToday($userId)
    {
        $todayStart = Carbon::today();

        // Bugün kapanan işlemlerden kar/zarar
        $closedProfit = Trade::where('user_id', $userId)
            ->where('status', 'closed')
            ->whereDate('close_time', '>=', $todayStart)
            ->sum('profit');

        // Açık pozisyonların kâr/zararı
        $positions = Position::where('user_id', $userId)
            ->where('status', 'open')
            ->whereDate('open_time', '>=', $todayStart)
            ->with('instrument')
            ->get();

        $openProfit = 0;

        foreach ($positions as $position) {
            $instrument = $position->instrument;
            $priceType = $position->direction === 'buy' ? 'bid' : 'ask';
            $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

            $openProfit += $this->calculatePositionProfit($position, $currentPrice);
        }

        return $closedProfit + $openProfit;
    }

    /**
     * Portföy özeti getir
     */
    public function getPortfolioSummary($userId)
    {
        $positions = Position::where('user_id', $userId)
            ->where('status', 'open')
            ->with('instrument')
            ->get();

        $summary = [
            'total_positions' => $positions->count(),
            'total_volume' => $positions->sum('volume'),
            'profit_positions' => 0,
            'loss_positions' => 0,
            'total_profit' => 0,
            'total_loss' => 0,
            'instruments' => [],
        ];

        foreach ($positions as $position) {
            $instrument = $position->instrument;
            $priceType = $position->direction === 'buy' ? 'bid' : 'ask';
            $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

            $profit = $this->calculatePositionProfit($position, $currentPrice);

            if ($profit >= 0) {
                $summary['profit_positions']++;
                $summary['total_profit'] += $profit;
            } else {
                $summary['loss_positions']++;
                $summary['total_loss'] += $profit; // Zaten negatif değer
            }

            // Enstrüman bazında grupla
            $symbol = $instrument->symbol;

            if (!isset($summary['instruments'][$symbol])) {
                $summary['instruments'][$symbol] = [
                    'symbol' => $symbol,
                    'name' => $instrument->name,
                    'positions' => 0,
                    'volume' => 0,
                    'profit' => 0,
                ];
            }

            $summary['instruments'][$symbol]['positions']++;
            $summary['instruments'][$symbol]['volume'] += $position->volume;
            $summary['instruments'][$symbol]['profit'] += $profit;
        }

        // Diziye dönüştür
        $summary['instruments'] = array_values($summary['instruments']);

        return $summary;
    }
}
