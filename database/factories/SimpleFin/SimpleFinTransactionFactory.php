<?php

namespace Database\Factories\SimpleFin;

use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpleFinTransactionFactory extends Factory
{
    protected $model = SimpleFinTransaction::class;

    public function definition()
    {
        return [
            'id' => 'TRX-' . $this->faker->uuid,
            'simple_fin_account_id' => SimpleFinAccount::factory(),
            'posted' => $this->faker->dateTimeThisMonth,
            'amount' => $this->faker->randomFloat(2, -1000, 1000),
            'description' => $this->faker->sentence,
            'payee' => $this->faker->company,
            'memo' => $this->faker->optional()->sentence,
            'transacted_at' => $this->faker->optional()->dateTimeThisMonth,
        ];
    }
}
