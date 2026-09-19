<?php

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_benefits', function (Blueprint $table) {
            $table->string('tracking_mode')->default(BenefitTrackingMode::Track);
            $table->string('value_kind')->default(BenefitValueKind::Currency);
            $table->unsignedInteger('quantity_total')->nullable();
            $table->string('reset_anchor')->default(BenefitResetAnchor::Calendar);
            $table->date('custom_reset_on')->nullable();
            $table->date('next_refresh_at')->nullable();
            $table->string('applies_to')->default(BenefitAppliesTo::Any);
            $table->string('location_country', 64)->nullable();
            $table->string('location_city', 128)->nullable();
            $table->unsignedBigInteger('loyalty_membership_id')->nullable();
            $table->string('required_channel')->nullable();
            $table->timestamps();

            $table->foreign('card_id')->references('id')->on('cards')->cascadeOnDelete();
            $table->foreign('loyalty_membership_id')->references('id')->on('loyalty_memberships')->nullOnDelete();

            $table->index(['tracking_mode', 'next_refresh_at']);
        });
    }

    public function down(): void
    {
        Schema::table('card_benefits', function (Blueprint $table) {
            $table->dropForeign(['card_id']);
            $table->dropForeign(['loyalty_membership_id']);
            $table->dropIndex(['tracking_mode', 'next_refresh_at']);
            $table->dropColumn([
                'tracking_mode',
                'value_kind',
                'quantity_total',
                'reset_anchor',
                'custom_reset_on',
                'next_refresh_at',
                'applies_to',
                'location_country',
                'location_city',
                'loyalty_membership_id',
                'required_channel',
                'created_at',
                'updated_at',
            ]);
        });
    }
};
