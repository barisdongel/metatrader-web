<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instrument extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'type', // 'forex', 'crypto', 'stocks', 'indices', 'commodities'
        'digits', // Fiyat hassasiyeti (ondalık basamak sayısı)
        'point', // En küçük fiyat değişimi (0.0001 forex, 0.01 stocks, vb.)
        'pip_value', // Standart lot (1.0) için pip değeri
        'contract_size', // Standart lot büyüklüğü
        'margin_required', // Gerekli teminat yüzdesi
        'description',
        'currency', // Ana para birimi
        'quote_currency', // Kotasyon para birimi
        'trading_hours', // İşlem saatleri JSON formatında
        'min_lot', // Minimum lot büyüklüğü
        'max_lot', // Maksimum lot büyüklüğü
        'lot_step', // Lot değişim adımı
        'swap_long', // Uzun pozisyon taşıma maliyeti
        'swap_short', // Kısa pozisyon taşıma maliyeti
        'is_popular', // Popüler/öne çıkan enstrüman mı?
        'is_active', // İşlem yapılabilir mi?
    ];

    protected $casts = [
        'digits' => 'integer',
        'point' => 'decimal:6',
        'pip_value' => 'decimal:2',
        'contract_size' => 'decimal:2',
        'margin_required' => 'decimal:2',
        'min_lot' => 'decimal:2',
        'max_lot' => 'decimal:2',
        'lot_step' => 'decimal:2',
        'swap_long' => 'decimal:2',
        'swap_short' => 'decimal:2',
        'trading_hours' => 'json',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Bu enstrümana ait işlemler
     */
    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    /**
     * Bu enstrümana ait açık pozisyonlar
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Bu enstrümanı favori olarak ekleyen kullanıcılar
     */
    public function favoriteByUsers()
    {
        return $this->belongsToMany(User::class, 'user_favorite_instruments');
    }

    /**
     * Pip değeri hesapla
     */
    public function calculatePipValue($volume)
    {
        return $this->pip_value * $volume;
    }

    /**
     * İşlem süresinde olup olmadığını kontrol et
     */
    public function isTradingHours()
    {
        if (empty($this->trading_hours)) {
            return true; // Varsayılan olarak her zaman açık
        }

        $hours = json_decode($this->trading_hours, true);
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));

        if (!isset($hours[$dayOfWeek])) {
            return false; // Bu gün için işlem saati belirtilmemiş
        }

        foreach ($hours[$dayOfWeek] as $interval) {
            $start = \Carbon\Carbon::parse($interval['start']);
            $end = \Carbon\Carbon::parse($interval['end']);

            if ($now->between($start, $end)) {
                return true;
            }
        }

        return false;
    }
}
