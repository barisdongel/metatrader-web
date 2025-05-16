<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instrument_id',
        'name',
        'type', // 'price', 'indicator'
        'condition', // 'above', 'below', 'crosses_above', 'crosses_below'
        'value',
        'indicator', // Eğer tip 'indicator' ise
        'indicator_params', // JSON formatında
        'is_active',
        'is_repeatable',
        'triggered_at',
        'notification_method', // 'email', 'push', 'both'
    ];

    protected $casts = [
        'indicator_params' => 'json',
        'is_active' => 'boolean',
        'is_repeatable' => 'boolean',
        'triggered_at' => 'datetime',
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
}
