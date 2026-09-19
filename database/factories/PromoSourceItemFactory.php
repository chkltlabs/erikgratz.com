<?php

namespace Database\Factories;

use App\Enums\PromoStatus;
use App\Models\PromoSourceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoSourceItem>
 */
class PromoSourceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => 'doctor_of_credit',
            'external_id' => $this->faker->unique()->uuid(),
            'title' => '30% transfer bonus to United',
            'facts' => [
                'type' => 'transfer_bonus',
                'bonus_percent' => 30,
            ],
            'status' => PromoStatus::PendingReview,
            'fetched_at' => now(),
        ];
    }
}
