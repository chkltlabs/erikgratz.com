<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_earning_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            $table->string('category');
            $table->string('channel');
            $table->string('vendor')->default('');
            $table->float('multiplier');
            $table->timestamps();

            $table->unique(['card_id', 'category', 'channel', 'vendor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_earning_rates');
    }
};
