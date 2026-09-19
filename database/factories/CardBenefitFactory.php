<?php

namespace Database\Factories;

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Enums\ResetPeriod;
use App\Models\Card;
use App\Models\CardBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardBenefit>
 */
class CardBenefitFactory extends Factory
{
    protected $model = CardBenefit::class;

    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'benefit' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'is_useable' => true,
            'is_used' => false,
            'value' => 300,
            'reset_period' => ResetPeriod::CalendarYearly,
            'tracking_mode' => BenefitTrackingMode::Track,
            'value_kind' => BenefitValueKind::Currency,
            'quantity_total' => null,
            'reset_anchor' => BenefitResetAnchor::Calendar,
            'applies_to' => BenefitAppliesTo::Any,
            'allowed_vendors' => null,
            'max_apply_per_use' => null,
            'award_only' => false,
        ];
    }
}
