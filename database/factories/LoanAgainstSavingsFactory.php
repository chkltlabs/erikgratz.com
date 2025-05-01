<?php

namespace Database\Factories;

use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanAgainstSavings>
 */
class LoanAgainstSavingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'balance' => $this->faker->randomFloat(),
            'reason' => $this->faker->word(),
            'loan_date' => $this->faker->date(),
            'paid_on' => $this->faker->date(),
            'is_paid' => false,
            'card_id' => Card::factory(),
        ];
    }
}
