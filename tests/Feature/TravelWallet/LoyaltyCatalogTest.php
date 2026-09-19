<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitTrackingMode;
use App\Enums\LoyaltyKind;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\TransferRoute;
use App\Services\TravelWallet\UnusedBenefitFeed;
use Database\Seeders\TravelWalletCatalogSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoyaltyCatalogTest extends TestCase
{
    #[Test]
    public function seeder_creates_programs_and_transfer_routes(): void
    {
        $this->seed(TravelWalletCatalogSeeder::class);

        $this->assertDatabaseHas('loyalty_programs', ['code' => 'hyatt']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'lufthansa']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'emirates']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'singapore']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'alaska']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'etihad']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'sas']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'tap']);
        $this->assertDatabaseHas('loyalty_programs', ['code' => 'finnair']);
        $this->assertSame(
            58,
            LoyaltyProgram::query()->where('kind', LoyaltyKind::Airline)->count()
        );
        $this->assertTrue(
            TransferRoute::query()->whereHas('program', fn ($q) => $q->where('code', 'united'))->exists()
        );
    }

    #[Test]
    public function conferred_status_benefits_appear_in_the_feed(): void
    {
        $card = Card::factory()->create();
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Hotel,
            'code' => 'hilton',
        ]);
        $membership = LoyaltyMembership::factory()->create([
            'loyalty_program_id' => $program->id,
            'conferred_by_card_id' => $card->id,
            'tier' => 'Gold',
        ]);
        CardBenefit::factory()->create([
            'card_id' => $card->id,
            'loyalty_membership_id' => $membership->id,
            'benefit' => 'Hilton Gold breakfast',
            'tracking_mode' => BenefitTrackingMode::Track,
            'applies_to' => BenefitAppliesTo::Hotel,
            'value' => 1,
        ]);

        $due = app(UnusedBenefitFeed::class)->due();

        $this->assertTrue($due->contains(fn (CardBenefit $benefit): bool => $benefit->benefit === 'Hilton Gold breakfast'));
    }
}
