<?php

namespace Database\Factories;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Activity;
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
            'is_income' => $this->faker->boolean,
            'type' => SpendType::getRandomValue(),
            'subtype' => SpendSubtype::getRandomValue(),
            'activity_id' => Activity::factory()
        ];
    }

    public function bare()
    {
        return $this->state(
            fn (array $attributes) => [
                'activity_id' => null
            ]);
    }


}
