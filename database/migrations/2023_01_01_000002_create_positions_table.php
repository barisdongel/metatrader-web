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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->string('direction', 10); // 'buy' veya 'sell'
            $table->decimal('volume', 10, 2); // Lot büyüklüğü
            $table->decimal('open_price', 15, 5);
            $table->decimal('current_price', 15, 5)->default(0);
            $table->decimal('take_profit', 15, 5)->nullable();
            $table->decimal('stop_loss', 15, 5)->nullable();
            $table->decimal('profit', 15, 2)->default(0);
            $table->decimal('swap', 15, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
            $table->timestamp('open_time');
            $table->string('status', 20)->default('open'); // 'open', 'closed', 'pending'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
