<?php

namespace Database\Factories;

use App\Enums\PointsProgram;
use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'user_id' => 1,
            'color' => $this->faker->hexColor(),
            'balance' => $this->faker->randomFloat(),
            'limit' => $this->faker->randomFloat(),
            'due_date' => $this->faker->numberBetween(1, 28),
            'pending' => $this->faker->randomFloat(),
            'interest_free_balance' => $this->faker->randomFloat(),
            'interest_saving_balance' => $this->faker->randomFloat(),
            'statement_date' => $this->faker->numberBetween(1, 28),
            'annual_fee' => $this->faker->randomNumber(3),
            'interest_free_balance_payment' => $this->faker->randomFloat(),
            'points_balance' => $this->faker->randomNumber(),
            'points_bonus' => $this->faker->randomNumber(),
            'points_bonus_spend' => $this->faker->randomNumber(),
            'points_bonus_period' => '+3 months',
            'date_opened' => $this->faker->date(),
            'points_program' => PointsProgram::getRandomValue()
        ];
    }
}
