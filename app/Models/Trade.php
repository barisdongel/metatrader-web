<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instrument_id',
        'direction', // 'buy' veya 'sell'
        'open_price',
        'close_price',
        'volume',
        'open_time',
        'close_time',
        'take_profit',
        'stop_loss',
        'profit',
        'commission',
        'swap',
        'status', // 'open', 'closed', 'cancelled', 'pending'
        'order_type', // 'market', 'limit', 'stop'
        'comment',
        'position_id',
    ];

    protected $casts = [
        'open_price' => 'decimal:5',
        'close_price' => 'decimal:5',
        'volume' => 'decimal:2',
        'profit' => 'decimal:2',
        'commission' => 'decimal:2',
        'swap' => 'decimal:2',
        'take_profit' => 'decimal:5',
        'stop_loss' => 'decimal:5',
        'open_time' => 'datetime',
        'close_time' => 'datetime',
    ];

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
     * İlişkili pozisyon
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * İşlemin toplam sonucunu hesapla
     */
    public function getTotalResultAttribute()
    {
        return $this->profit - $this->commission - $this->swap;
    }

    /**
     * İşlemin açık olup olmadığını kontrol et
     */
    public function getIsOpenAttribute()
    {
        return $this->status === 'open';
    }
}
