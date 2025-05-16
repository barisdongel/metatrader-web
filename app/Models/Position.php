<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instrument_id',
        'direction', // 'buy' veya 'sell'
        'volume',
        'open_price',
        'current_price',
        'take_profit',
        'stop_loss',
        'profit',
        'swap',
        'commission',
        'open_time',
        'status', // 'open', 'closed', 'pending'
    ];

    protected $casts = [
        'open_price' => 'decimal:5',
        'current_price' => 'decimal:5',
        'volume' => 'decimal:2',
        'profit' => 'decimal:2',
        'commission' => 'decimal:2',
        'swap' => 'decimal:2',
        'take_profit' => 'decimal:5',
        'stop_loss' => 'decimal:5',
        'open_time' => 'datetime',
    ];

    protected $appends = ['current_profit'];

    /**
     * İlişkili kullanıcı
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * İlişkili enstrüman
     */
    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }

    /**
     * Pozisyona ait işlemler
     */
    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    /**
     * Anlık kar/zarar hesapla
     */
    public function getCurrentProfitAttribute()
    {
        if ($this->current_price == 0) {
            return 0;
        }

        $priceDiff = $this->direction === 'buy'
            ? $this->current_price - $this->open_price
            : $this->open_price - $this->current_price;

        $pipValue = $this->instrument->pip_value * $this->volume;
        $pipsCount = $priceDiff / $this->instrument->point;

        return $pipsCount * $pipValue;
    }

    /**
     * Toplam sonucu hesapla (kar/zarar + swap + komisyon)
     */
    public function getTotalResultAttribute()
    {
        return $this->current_profit + $this->swap - $this->commission;
    }
}
