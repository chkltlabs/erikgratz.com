<?php

use App\Enums\BookingCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_intents', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default(BookingCategory::Flight);
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('vendor')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('cash_price', 10, 2)->nullable();
            $table->json('award_quotes')->nullable();
            $table->json('ranking')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_intents');
    }
};
