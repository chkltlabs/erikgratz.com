<?php

namespace Database\Factories;

use App\Enums\BenefitAppliesTo;
use App\Models\BookingPerk;
use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPerk>
 */
class BookingPerkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'decision_value' => 40,
            'applies_to' => BenefitAppliesTo::Hotel,
            'award_only' => false,
        ];
    }
}
