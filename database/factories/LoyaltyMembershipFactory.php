<?php

namespace Database\Factories;

use App\Enums\PointsProgram;
use App\Models\Card;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyMembership>
 */
class LoyaltyMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'loyalty_program_id' => LoyaltyProgram::factory(),
            'tier' => 'Gold',
            'loyalty_number' => $this->faker->numerify('##########'),
            'conferred_by_card_id' => fn (array $attributes) => Card::factory()->create([
                'user_id' => $attributes['user_id'],
                'points_program' => PointsProgram::Unknown,
                'points_balance' => 0,
            ]),
            'points_balance' => 10000,
        ];
    }
}
