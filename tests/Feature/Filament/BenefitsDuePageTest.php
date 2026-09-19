<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Filament\Resources\CardBenefits\Pages\ListCardBenefits;
use App\Models\CardBenefit;
use App\Models\User;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BenefitsDuePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function page_lists_tracked_unused_benefits(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$benefit]);
    }

    #[Test]
    public function use_fully_hides_the_row_without_a_reload(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->assertCanSeeTableRecords([$benefit])
            ->callTableAction('useFully', $benefit)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Benefit marked used')
            ->assertCanNotSeeTableRecords([$benefit]);

        $this->assertSame(0.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function use_partial_keeps_the_row_and_updates_remaining(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->callTableAction('usePartial', $benefit, data: [
                'units' => 80,
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Usage recorded')
            ->assertCanSeeTableRecords([$benefit])
            ->assertSee('$220.00')
            ->assertDontSee('$300.00');
    }

    #[Test]
    public function ignore_action_hides_the_row(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Skip me',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 50,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->callTableAction('ignore', $benefit)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Benefit ignored')
            ->assertCanNotSeeTableRecords([$benefit]);

        $this->assertTrue($benefit->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
    }

    #[Test]
    public function bulk_use_fully_hides_selected_rows_without_a_reload(): void
    {
        $first = CardBenefit::factory()->create([
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
        ]);
        $second = CardBenefit::factory()->create([
            'benefit' => 'Dining credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 100,
            'value_kind' => BenefitValueKind::Currency,
        ]);
        $untouched = CardBenefit::factory()->create([
            'benefit' => 'Airline credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 200,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->assertCanSeeTableRecords([$first, $second, $untouched])
            ->mountTableBulkAction('useFully', [$first, $second])
            ->assertTableBulkActionMounted('useFully')
            ->callMountedTableBulkAction()
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Selected benefits marked used')
            ->assertCanNotSeeTableRecords([$first, $second])
            ->assertCanSeeTableRecords([$untouched]);

        $this->assertSame(0.0, $first->fresh()->remaining());
        $this->assertSame(0.0, $second->fresh()->remaining());
        $this->assertSame(200.0, $untouched->fresh()->remaining());
    }

    #[Test]
    public function bulk_ignore_hides_selected_rows_without_a_reload(): void
    {
        $first = CardBenefit::factory()->create([
            'benefit' => 'Skip lounge',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 50,
        ]);
        $second = CardBenefit::factory()->create([
            'benefit' => 'Skip golf',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 75,
        ]);
        $untouched = CardBenefit::factory()->create([
            'benefit' => 'Keep hotel',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->mountTableBulkAction('ignore', [$first, $second])
            ->assertTableBulkActionMounted('ignore')
            ->callMountedTableBulkAction()
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Selected benefits ignored')
            ->assertCanNotSeeTableRecords([$first, $second])
            ->assertCanSeeTableRecords([$untouched]);

        $this->assertTrue($first->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
        $this->assertTrue($second->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
        $this->assertTrue($untouched->fresh()->tracking_mode->is(BenefitTrackingMode::Track));
    }

    #[Test]
    public function auto_credit_hides_the_row_without_a_reload(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$benefit])
            ->set('activeTab', 'auto')
            ->assertCanSeeTableRecords([$benefit])
            ->callTableAction('creditPeriod', $benefit)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Period credited')
            ->assertCanNotSeeTableRecords([$benefit]);
    }

    #[Test]
    public function auto_bulk_credit_hides_selected_rows_without_a_reload(): void
    {
        $first = CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
            'value_kind' => BenefitValueKind::Currency,
        ]);
        $second = CardBenefit::factory()->create([
            'benefit' => 'Uber Cash',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 15,
            'value_kind' => BenefitValueKind::Currency,
        ]);
        $untouched = CardBenefit::factory()->create([
            'benefit' => 'Walmart+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 13,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->assertCanSeeTableRecords([$first, $second, $untouched])
            ->mountTableBulkAction('creditPeriod', [$first, $second])
            ->assertTableBulkActionMounted('creditPeriod')
            ->callMountedTableBulkAction()
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('Selected periods credited')
            ->assertCanNotSeeTableRecords([$first, $second])
            ->assertCanSeeTableRecords([$untouched]);

        $this->assertSame(0.0, $first->fresh()->remaining());
        $this->assertSame(0.0, $second->fresh()->remaining());
        $this->assertSame(13.0, $untouched->fresh()->remaining());
    }

    #[Test]
    public function auto_use_fully_sets_standing_full_and_credits_this_window(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'auto_assume_amount' => 5,
            'value' => 14,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->callTableAction('useFully', $benefit)
            ->assertHasNoTableActionErrors()
            ->assertCanNotSeeTableRecords([$benefit]);

        $this->assertNull($benefit->fresh()->auto_assume_amount);
        $this->assertSame(0.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function auto_use_partial_sets_standing_amount_and_credits_it(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Uber Cash',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 15,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->callTableAction('usePartial', $benefit, data: ['units' => 5])
            ->assertHasNoTableActionErrors()
            ->assertCanSeeTableRecords([$benefit])
            ->assertSee('Assume $5.00');

        $this->assertEquals(5.0, $benefit->fresh()->auto_assume_amount);
        $this->assertSame(10.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function auto_ignore_leaves_the_assumed_tab(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Walmart+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 13,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->callTableAction('ignore', $benefit)
            ->assertHasNoTableActionErrors()
            ->assertCanNotSeeTableRecords([$benefit]);

        $this->assertTrue($benefit->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
        $this->assertSame(13.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function auto_credit_is_a_noop_when_nothing_remains(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->callTableAction('creditPeriod', $benefit)
            ->assertCanNotSeeTableRecords([$benefit]);

        $this->assertSame(0.0, $benefit->fresh()->remaining());
        $this->assertNull(app(BenefitUsageRecorder::class)->creditThisPeriod($benefit->fresh()));
    }

    #[Test]
    public function ignored_benefits_are_hidden_by_default(): void
    {
        $tracked = CardBenefit::factory()->create([
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
        ]);
        $ignored = CardBenefit::factory()->create([
            'benefit' => 'Lounge access',
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'value' => 50,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->assertCanSeeTableRecords([$tracked])
            ->assertCanNotSeeTableRecords([$ignored]);
    }

    #[Test]
    public function showing_all_lists_ignored_benefits_last_and_greyed(): void
    {
        $tracked = CardBenefit::factory()->create([
            'benefit' => 'Zebra lounge',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 300,
            'next_refresh_at' => now()->addMonth()->toDateString(),
        ]);
        $ignored = CardBenefit::factory()->create([
            'benefit' => 'Alpha lounge',
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'value' => 50,
            'next_refresh_at' => now()->addDay()->toDateString(),
        ]);

        Livewire::test(ListCardBenefits::class)
            ->filterTable('ignored', null)
            ->assertCanSeeTableRecords([$tracked, $ignored])
            ->assertSeeInOrder(['Zebra lounge', 'Alpha lounge'])
            ->assertSee('opacity-50', escape: false);
    }

    #[Test]
    public function assumed_tab_hides_ignored_benefits_by_default(): void
    {
        $auto = CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
        ]);
        $ignored = CardBenefit::factory()->create([
            'benefit' => 'Walmart+',
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'value' => 13,
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->assertCanSeeTableRecords([$auto])
            ->assertCanNotSeeTableRecords([$ignored]);
    }

    #[Test]
    public function assumed_tab_showing_all_lists_ignored_benefits_last_and_greyed(): void
    {
        $auto = CardBenefit::factory()->create([
            'benefit' => 'Zebra cash',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
            'next_refresh_at' => now()->addMonth()->toDateString(),
        ]);
        $ignored = CardBenefit::factory()->create([
            'benefit' => 'Alpha cash',
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'value' => 13,
            'next_refresh_at' => now()->addDay()->toDateString(),
        ]);

        Livewire::test(ListCardBenefits::class)
            ->set('activeTab', 'auto')
            ->filterTable('ignored', null)
            ->assertCanSeeTableRecords([$auto, $ignored])
            ->assertSeeInOrder(['Zebra cash', 'Alpha cash'])
            ->assertSee('opacity-50', escape: false);
    }
}
