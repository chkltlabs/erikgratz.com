<?php

namespace Database\Factories\SimpleFin;

use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpleFinAccountFactory extends Factory
{
    protected $model = SimpleFinAccount::class;

    public function definition()
    {
        return [
            'id' => 'ACT-' . $this->faker->uuid,
            'user_id' => User::factory(),
            'simple_fin_organization_id' => SimpleFinOrganization::factory(),
            'name' => $this->faker->words(3, true),
            'currency' => 'USD',
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'available_balance' => $this->faker->randomFloat(2, 0, 10000),
            'balance_date' => now(),
        ];
    }
}
