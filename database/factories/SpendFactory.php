<?php

namespace Database\Factories;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Activity;
use App\Models\Payment;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpendFactory extends Factory
{
    protected $model = Spend::class;

    public function definition(): array
    {
        $type = SpendType::getRandomValue();
        $subtypes = array_keys(SpendSubtype::getFilteredSet($type));
        $subtype = !empty($subtypes) ? $this->faker->randomElement($subtypes) : SpendSubtype::Other;

        return [
            'name' => $this->faker->name(),
            'is_income' => false,
            'type' => $type,
            'subtype' => $subtype,
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

    public function noPayments()
    {
        return $this->newInstance(['afterCreating' => collect()]);
    }

    public function configure()
    {
        return $this->afterCreating(
            fn ($model) => Payment::factory(4)
                ->recycle($model)
                ->create()
        );
    }
}
