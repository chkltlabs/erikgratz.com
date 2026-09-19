<?php

use App\Enums\PromoStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_source_items', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('external_id');
            $table->string('title', 512);
            $table->json('facts')->nullable();
            $table->string('status')->default(PromoStatus::PendingReview);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_source_items');
    }
};
