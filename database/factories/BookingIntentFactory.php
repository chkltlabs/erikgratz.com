<?php

namespace Database\Factories;

use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Models\BookingIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingIntent>
 */
class BookingIntentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => BookingCategory::Flight,
            'vendor' => 'United',
            'destination' => 'Tokyo',
            'cash_price' => 1200,
            'cabin' => BookingCabin::Economy,
            'award_quotes' => [],
            'ranking' => [],
        ];
    }
}
