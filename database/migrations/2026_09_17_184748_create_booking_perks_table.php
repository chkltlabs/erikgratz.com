<?php

use App\Enums\BenefitAppliesTo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_perks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_program_id')->nullable()->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('loyalty_membership_id')->nullable()->constrained('loyalty_memberships')->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained('cards')->cascadeOnDelete();
            $table->foreignId('card_benefit_id')->nullable()->constrained('card_benefits')->cascadeOnDelete();
            $table->string('min_tier')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->float('decision_value')->default(0);
            $table->string('applies_to')->default(BenefitAppliesTo::Any);
            $table->string('channel')->nullable();
            $table->boolean('award_only')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_perks');
    }
};
