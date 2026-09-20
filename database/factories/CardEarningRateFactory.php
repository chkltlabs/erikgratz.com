<?php

namespace Database\Factories;

use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Models\Card;
use App\Models\CardEarningRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardEarningRate>
 */
class CardEarningRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'category' => BookingCategory::Hotel,
            'channel' => BookingChannel::Direct,
            'vendor' => null,
            'multiplier' => 3,
        ];
    }
}
