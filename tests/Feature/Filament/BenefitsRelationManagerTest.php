<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Enums\BookingChannel;
use App\Enums\ResetPeriod;
use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Filament\Resources\CardResource\RelationManagers\BenefitsRelationManager;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BenefitsRelationManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function use_fully_refreshes_remaining_without_a_reload(): void
    {
        $card = Card::factory()->create();
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->assertCanSeeTableRecords([$benefit])
            ->assertSee('$300.00')
            ->callTableAction('useFully', $benefit)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Benefit marked used')
            ->assertCanSeeTableRecords([$benefit])
            ->assertSee('$0.00');

        $this->assertSame(0.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function bulk_use_fully_refreshes_remaining_without_a_reload(): void
    {
        $card = Card::factory()->create();
        $first = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
        ]);
        $second = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Dining credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 100,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->callTableBulkAction('useFully', [$first, $second])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Selected benefits marked used')
            ->assertCanSeeTableRecords([$first, $second])
            ->assertSee('$0.00');

        $this->assertSame(0.0, $first->fresh()->remaining());
        $this->assertSame(0.0, $second->fresh()->remaining());
    }

    #[Test]
    public function bulk_ignore_updates_tracking_mode(): void
    {
        $card = Card::factory()->create();
        $first = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Skip lounge',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 50,
        ]);
        $second = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Skip golf',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 75,
        ]);

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->callTableBulkAction('ignore', [$first, $second])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Selected benefits ignored');

        $this->assertTrue($first->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
        $this->assertTrue($second->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
    }

    #[Test]
    public function create_form_saves_an_auto_benefit_and_refreshes(): void
    {
        $card = Card::factory()->create();

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->callTableAction('create', data: [
                'benefit' => 'Airline credit',
                'is_useable' => true,
                'tracking_mode' => BenefitTrackingMode::Auto,
                'auto_assume_amount' => 100,
                'description' => 'BA statement credit',
                'value_kind' => BenefitValueKind::Currency,
                'value' => 200,
                'quantity_total' => 2,
                'reset_period' => ResetPeriod::CalendarYearly,
                'reset_anchor' => BenefitResetAnchor::Calendar,
                'applies_to' => BenefitAppliesTo::Flight,
                'required_channel' => BookingChannel::Direct,
                'location_country' => 'US',
                'location_city' => 'NYC',
                'allowed_vendors' => ['british airways'],
                'award_only' => false,
                'max_apply_per_use' => [
                    'default' => 100,
                    'economy' => 50,
                    'premium_economy' => 75,
                    'business' => 150,
                    'first' => 200,
                ],
            ])
            ->assertHasNoTableActionErrors();

        $benefit = CardBenefit::query()
            ->where('card_id', $card->id)
            ->where('benefit', 'Airline credit')
            ->first();

        $this->assertNotNull($benefit);
        $this->assertTrue($benefit->tracking_mode->is(BenefitTrackingMode::Auto));
        $this->assertSame(100.0, (float) $benefit->auto_assume_amount);
        $this->assertSame(200.0, (float) $benefit->value);
    }

    #[Test]
    public function edit_form_can_change_tracking_mode(): void
    {
        $card = Card::factory()->create();
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Dining credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 100,
        ]);

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->callTableAction('edit', $benefit, data: [
                'benefit' => 'Dining credit',
                'tracking_mode' => BenefitTrackingMode::Ignore,
                'value_kind' => BenefitValueKind::Currency,
                'value' => 100,
                'applies_to' => BenefitAppliesTo::Other,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue($benefit->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
    }

    #[Test]
    public function ignored_benefits_are_hidden_by_default(): void
    {
        $card = Card::factory()->create();
        $tracked = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
        ]);
        $ignored = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Lounge access',
            'tracking_mode' => BenefitTrackingMode::Ignore,
        ]);

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->assertCanSeeTableRecords([$tracked])
            ->assertCanNotSeeTableRecords([$ignored]);
    }

    #[Test]
    public function showing_all_lists_ignored_benefits_last_and_greyed(): void
    {
        $card = Card::factory()->create();
        $tracked = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Zebra lounge',
            'tracking_mode' => BenefitTrackingMode::Track,
        ]);
        $ignored = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Alpha lounge',
            'tracking_mode' => BenefitTrackingMode::Ignore,
        ]);

        Livewire::test(BenefitsRelationManager::class, [
            'ownerRecord' => $card,
            'pageClass' => EditCard::class,
        ])
            ->filterTable('ignored', null)
            ->assertCanSeeTableRecords([$tracked, $ignored])
            ->assertSeeInOrder(['Zebra lounge', 'Alpha lounge'])
            ->assertSee('opacity-50', escape: false);
    }
}
