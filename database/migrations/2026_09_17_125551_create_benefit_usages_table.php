<?php

use App\Enums\BenefitUsageSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_benefit_id')->constrained('card_benefits')->cascadeOnDelete();
            $table->date('used_on');
            $table->decimal('amount', 10, 2)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('source')->default(BenefitUsageSource::Manual);
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('notes', 1024)->nullable();
            $table->timestamps();

            $table->index(['card_benefit_id', 'used_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_usages');
    }
};
