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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type', 20); // 'price', 'indicator'
            $table->string('condition', 20); // 'above', 'below', 'crosses_above', 'crosses_below'
            $table->decimal('value', 15, 5);
            $table->string('indicator')->nullable(); // Eğer tip 'indicator' ise
            $table->json('indicator_params')->nullable(); // JSON formatında
            $table->boolean('is_active')->default(true);
            $table->boolean('is_repeatable')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->string('notification_method', 20)->default('email'); // 'email', 'push', 'both'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
