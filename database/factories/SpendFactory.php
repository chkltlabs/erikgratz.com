<?php

namespace Database\Factories;

use App\Models\Spend;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SpendFactory extends Factory
{
    protected $model = Spend::class;

    public function definition(): array
    {
        return [
            'spend_for' => Carbon::now(),
            'spend_at' => Carbon::now(),
            'name' => $this->faker->name(),
            'amount' => $this->faker->randomFloat(),
            'type' => $this->faker->word(),
            'subtype' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }


}
