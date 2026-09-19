<?php

namespace Database\Factories;

use App\Enums\BookingCategory;
use App\Enums\PromoStatus;
use App\Models\Card;
use App\Models\EarningPromotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarningPromotion>
 */
class EarningPromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'category' => BookingCategory::Hotel,
            'multiplier' => 5,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => PromoStatus::Active,
            'summary' => '5x hotels',
        ];
    }
}
