<?php

use App\Enums\PromoStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_route_id');
            $table->unsignedBigInteger('promo_source_item_id')->nullable();
            $table->unsignedInteger('bonus_percent')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('status')->default(PromoStatus::PendingReview);
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_bonuses');
    }
};
