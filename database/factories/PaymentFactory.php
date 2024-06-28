<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Payment;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(),
            'is_paid' => $this->faker->boolean(),
            'paid_on' => Carbon::now(),
            'spend_id' => Spend::factory(),
            'card_id' => Card::factory(),
        ];
    }
}
