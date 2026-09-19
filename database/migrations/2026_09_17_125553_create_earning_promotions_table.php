<?php

use App\Enums\PromoStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earning_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->nullable()->constrained('cards')->cascadeOnDelete();
            $table->string('points_program')->nullable();
            $table->unsignedBigInteger('promo_source_item_id')->nullable();
            $table->string('category')->nullable();
            $table->string('merchant')->nullable();
            $table->decimal('multiplier', 8, 2)->default(1);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('status')->default(PromoStatus::PendingReview);
            $table->string('summary', 512)->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earning_promotions');
    }
};
