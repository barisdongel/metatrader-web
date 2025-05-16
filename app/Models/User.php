<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_balance',
        'account_currency',
        'account_type',
        'demo_account',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'account_balance' => 'decimal:2',
        'demo_account' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Kullanıcının işlemleri
     */
    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    /**
     * Kullanıcının açık pozisyonları
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Kullanıcının kayıtlı grafik şablonları
     */
    public function chartTemplates()
    {
        return $this->hasMany(ChartTemplate::class);
    }

    /**
     * Kullanıcının favori enstrümanları
     */
    public function favoriteInstruments()
    {
        return $this->belongsToMany(Instrument::class, 'user_favorite_instruments');
    }

    /**
     * Kullanıcının alarm ayarları
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}
