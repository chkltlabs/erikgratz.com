<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'start_date' => Carbon::now()
                ->subMonths(rand(1, 40))
                ->subDays(rand(4, 10))
                ->toDateString(),
            'end_date' => Carbon::now()->addMonths(rand(1, 3))->toDateString(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(
            fn ($model) => Spend::factory(4)
                ->recycle($model)
                ->create());
    }
}
