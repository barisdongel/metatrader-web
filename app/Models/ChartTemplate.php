<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'config',
        'is_default',
    ];

    protected $casts = [
        'config' => 'json',
        'is_default' => 'boolean',
    ];

    /**
     * Bu şablonun sahibi olan kullanıcı
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
