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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->foreignId('position_id')->nullable()->constrained()->onDelete('set null');
            $table->string('direction', 10); // 'buy' veya 'sell'
            $table->decimal('open_price', 15, 5);
            $table->decimal('close_price', 15, 5)->nullable();
            $table->decimal('volume', 10, 2); // Lot büyüklüğü
            $table->timestamp('open_time');
            $table->timestamp('close_time')->nullable();
            $table->decimal('take_profit', 15, 5)->nullable();
            $table->decimal('stop_loss', 15, 5)->nullable();
            $table->decimal('profit', 15, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
            $table->decimal('swap', 15, 2)->default(0);
            $table->string('status', 20); // 'open', 'closed', 'cancelled', 'pending'
            $table->string('order_type', 20); // 'market', 'limit', 'stop'
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
