<?php

namespace Database\Factories;

use App\Enums\PointsProgram;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointRedemption>
 */
class PointRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'activity_id' => Activity::factory(),
            'type' => SpendType::getRandomValue(),
            'subtype' => SpendSubtype::getRandomValue(),
            'paid_on' => $this->faker->date(),
            'points_program' => PointsProgram::getRandomValue(),
            'points_spent' => rand(1000, 1000000),
            'money_spent' => $this->faker->randomFloat(2, 10, 2000),
            'cash_value' => $this->faker->randomFloat(2, 100, 40000),
        ];
    }
}
