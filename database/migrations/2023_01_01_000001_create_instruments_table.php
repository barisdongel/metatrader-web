<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol')->unique();
            $table->string('type', 20); // 'forex', 'crypto', 'stocks', 'indices', 'commodities'
            $table->integer('digits')->default(5); // Fiyat hassasiyeti (ondalık basamak sayısı)
            $table->decimal('point', 10, 6)->default(0.00001); // En küçük fiyat değişimi
            $table->decimal('pip_value', 10, 2)->default(10.00); // Standart lot (1.0) için pip değeri
            $table->decimal('contract_size', 10, 2)->default(100000.00); // Standart lot büyüklüğü
            $table->decimal('margin_required', 5, 2)->default(3.33); // Gerekli teminat yüzdesi
            $table->text('description')->nullable();
            $table->string('currency', 10)->default('USD'); // Ana para birimi
            $table->string('quote_currency', 10)->default('USD'); // Kotasyon para birimi
            $table->json('trading_hours')->nullable(); // İşlem saatleri JSON formatında
            $table->decimal('min_lot', 5, 2)->default(0.01); // Minimum lot büyüklüğü
            $table->decimal('max_lot', 10, 2)->default(100.00); // Maksimum lot büyüklüğü
            $table->decimal('lot_step', 5, 2)->default(0.01); // Lot değişim adımı
            $table->decimal('swap_long', 10, 2)->default(0); // Uzun pozisyon taşıma maliyeti
            $table->decimal('swap_short', 10, 2)->default(0); // Kısa pozisyon taşıma maliyeti
            $table->boolean('is_popular')->default(false); // Popüler/öne çıkan enstrüman mı?
            $table->boolean('is_active')->default(true); // İşlem yapılabilir mi?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
