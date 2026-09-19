<?php

namespace Database\Factories;

use App\Enums\LoyaltyKind;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyProgram>
 */
class LoyaltyProgramFactory extends Factory
{
    public function definition(): array
    {
        $code = strtolower($this->faker->unique()->lexify('????'));

        return [
            'kind' => LoyaltyKind::Hotel,
            'name' => ucfirst($code).' Rewards',
            'code' => $code,
        ];
    }
}
