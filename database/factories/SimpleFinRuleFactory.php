<?php

namespace Database\Factories;

use App\Models\SimpleFinRule;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpleFinRuleFactory extends Factory
{
    protected $model = SimpleFinRule::class;

    public function definition()
    {
        return [
            'pattern' => $this->faker->word,
            'spend_type' => 'spend',
            'spend_id' => Spend::factory(),
        ];
    }
}
