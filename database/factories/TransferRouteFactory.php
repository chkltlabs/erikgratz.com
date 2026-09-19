<?php

namespace Database\Factories;

use App\Enums\PointsProgram;
use App\Models\LoyaltyProgram;
use App\Models\TransferRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferRoute>
 */
class TransferRouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_program' => PointsProgram::ChaseUltimateRewards,
            'loyalty_program_id' => LoyaltyProgram::factory(),
            'base_ratio' => 1,
            'is_active' => true,
        ];
    }
}
