<?php

namespace Database\Factories;

use App\Enums\Period;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PeriodicSpend>
 */
class PeriodicSpendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'is_income' => false,
            'type' => SpendType::getRandomValue(),
            'subtype' => SpendSubtype::getRandomValue(),
            'period' => Period::getRandomValue(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
        ];
    }
}
