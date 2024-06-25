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
            'spend_for' => Carbon::now()->addDays(rand(1, 30)),
            'spend_at' => Carbon::now()->subDays(rand(1, 7)),
            'name' => $this->faker->name(),
            'amount' => $this->faker->randomFloat(max: 1000),
            'is_income' => $this->faker->boolean,
            'type' => SpendType::getRandomValue(),
            'subtype' => SpendSubtype::getRandomValue(),
            'activity_id' => Activity::factory(),
        ];
    }

    public function bare()
    {
        return $this->state(
            fn (array $attributes) => [
                'activity_id' => null,
            ]);
    }
}
