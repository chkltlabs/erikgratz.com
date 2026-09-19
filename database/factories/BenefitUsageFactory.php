<?php

namespace Database\Factories;

use App\Enums\BenefitUsageSource;
use App\Models\BenefitUsage;
use App\Models\CardBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenefitUsage>
 */
class BenefitUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_benefit_id' => CardBenefit::factory(),
            'used_on' => now()->toDateString(),
            'amount' => 50,
            'quantity' => null,
            'source' => BenefitUsageSource::Manual,
        ];
    }
}
