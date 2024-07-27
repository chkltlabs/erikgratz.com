<?php

namespace Database\Factories;

use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'balance' => $this->faker->randomFloat(),
            'limit' => $this->faker->randomFloat(),
            'due_date' => $this->faker->randomNumber(),
            'pending' => $this->faker->randomFloat(),
            'interest_free_balance' => $this->faker->randomFloat(),
            'interest_saving_balance' => $this->faker->randomFloat(),
        ];
    }
}
