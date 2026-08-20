<?php

namespace Database\Factories;

use App\Enums\TravelMethod;
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
            'location_name' => null,
            'latitude' => null,
            'longitude' => null,
            'travel_method' => null,
        ];
    }

    public function withLocation(
        string $name = 'Istanbul, Turkey',
        float $latitude = 41.0082,
        float $longitude = 28.9784,
        ?string $travelMethod = TravelMethod::Plane,
    ): static {
        return $this->state(fn (): array => [
            'location_name' => $name,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'travel_method' => $travelMethod,
        ]);
    }

    public function configure()
    {
        return $this->afterCreating(
            fn ($model) => Spend::factory(4)
                ->recycle($model)
                ->create());
    }
}
