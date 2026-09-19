<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\BenefitUsageSource;
use App\Models\BenefitUsage;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Services\TravelWallet\FeeRoiCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeeRoiTest extends TestCase
{
    #[Test]
    public function captured_value_this_year_is_compared_to_annual_fee(): void
    {
        $card = Card::factory()->create([
            'annual_fee' => 550,
            'date_opened' => now()->subMonths(4)->toDateString(),
        ]);
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'value' => 300,
        ]);

        BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'used_on' => now()->subDay()->toDateString(),
            'amount' => 200,
            'source' => BenefitUsageSource::Manual,
        ]);
        BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'used_on' => now()->subDay()->toDateString(),
            'amount' => 300,
            'source' => BenefitUsageSource::AssumedAuto,
        ]);
        BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'used_on' => now()->subYears(2)->toDateString(),
            'amount' => 999,
            'source' => BenefitUsageSource::Manual,
        ]);

        $roi = app(FeeRoiCalculator::class)->forCard($card);

        $this->assertEquals(500.0, $roi['captured']);
        $this->assertEquals(550.0, $roi['fee']);
        $this->assertEquals(-50.0, $roi['net']);
    }
}
