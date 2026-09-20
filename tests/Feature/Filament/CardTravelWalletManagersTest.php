<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BenefitAppliesTo;
use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Filament\Resources\CardResource\RelationManagers\BookingPerksRelationManager;
use App\Filament\Resources\CardResource\RelationManagers\EarningRatesRelationManager;
use App\Filament\Resources\CardResource\Widgets\CardFeeRoi;
use App\Models\BenefitUsage;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardTravelWalletManagersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function booking_perks_relation_manager_can_create_a_perk(): void
    {
        $card = Card::factory()->create();

        Livewire::test(BookingPerksRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'name' => 'Priority boarding',
                'description' => 'Group 1',
                'decision_value' => 25,
                'applies_to' => BenefitAppliesTo::Flight,
                'channel' => BookingChannel::Direct,
                'award_only' => false,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(
            BookingPerk::query()
                ->where('card_id', $card->id)
                ->where('name', 'Priority boarding')
                ->exists()
        );
    }

    #[Test]
    public function earning_rates_relation_manager_can_create_a_rate(): void
    {
        $card = Card::factory()->create();

        Livewire::test(EarningRatesRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'category' => BookingCategory::Hotel,
                'channel' => BookingChannel::Direct,
                'vendor' => 'Hyatt',
                'multiplier' => 5,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(
            CardEarningRate::query()
                ->where('card_id', $card->id)
                ->where('multiplier', 5)
                ->exists()
        );
    }

    #[Test]
    public function earning_rates_relation_manager_stores_a_blank_vendor_as_null(): void
    {
        $card = Card::factory()->create();
        $rate = CardEarningRate::factory()->create([
            'card_id' => $card->id,
            'vendor' => 'Hyatt',
            'multiplier' => 4,
        ]);

        Livewire::test(EarningRatesRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->callTableAction('edit', $rate, data: [
                'category' => BookingCategory::Hotel,
                'channel' => BookingChannel::Direct,
                'vendor' => null,
                'multiplier' => 4,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertNull($rate->fresh()->vendor);
    }

    #[Test]
    public function fee_roi_widget_shows_captured_value_against_the_annual_fee(): void
    {
        $card = Card::factory()->create([
            'annual_fee' => 550,
            'date_opened' => now()->subMonths(3)->toDateString(),
        ]);
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'value' => 300,
        ]);
        BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'used_on' => now()->toDateString(),
            'amount' => 200,
        ]);

        Livewire::test(CardFeeRoi::class, ['record' => $card])
            ->assertSuccessful()
            ->call('refreshFromBenefitUsage')
            ->assertSee('Benefit value this year')
            ->assertSee('200.00')
            ->assertSee('550.00');
    }

    #[Test]
    public function fee_roi_widget_is_empty_without_a_card(): void
    {
        Livewire::test(CardFeeRoi::class)
            ->assertSuccessful()
            ->assertDontSee('Benefit value this year');
    }
}
