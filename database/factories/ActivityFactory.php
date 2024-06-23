<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Spend;
use Closure;
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
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now(),
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
