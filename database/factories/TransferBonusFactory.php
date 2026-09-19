<?php

namespace Database\Factories;

use App\Enums\PromoStatus;
use App\Models\TransferBonus;
use App\Models\TransferRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferBonus>
 */
class TransferBonusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transfer_route_id' => TransferRoute::factory(),
            'bonus_percent' => 30,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => PromoStatus::Active,
        ];
    }
}
