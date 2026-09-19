<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitUsageSource;
use App\Enums\BenefitValueKind;
use App\Enums\ResetPeriod;
use App\Jobs\DailyUpkeep;
use App\Jobs\DebitIFB;
use App\Jobs\GuessISB;
use App\Jobs\ZeroISB;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitRefresher;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BenefitLedgerTest extends TestCase
{
    #[Test]
    public function remaining_subtracts_usages_in_the_current_window_only(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
            'reset_period' => ResetPeriod::CalendarYearly,
        ]);

        app(BenefitUsageRecorder::class)->record($benefit, 80, now());
        app(BenefitUsageRecorder::class)->record($benefit, 40, now());

        $this->assertEquals(180.0, $benefit->fresh()->remaining());
        $this->assertFalse($benefit->fresh()->is_used);
        $this->assertNull($benefit->usages()->first()->quantity);
    }

    #[Test]
    public function currency_use_count_is_written_when_quantity_total_is_set(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 500,
            'value_kind' => BenefitValueKind::Currency,
            'quantity_total' => 2,
            'reset_period' => ResetPeriod::CalendarYearly,
        ]);

        $usage = app(BenefitUsageRecorder::class)->record($benefit, 250, now());

        $this->assertEquals(1, $usage->quantity);
        $this->assertEquals(250.0, $usage->amount);
        $this->assertSame(1, $benefit->fresh()->remainingUses());
    }

    #[Test]
    public function use_fully_exhausts_the_window(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 100,
            'value_kind' => BenefitValueKind::Currency,
            'reset_period' => ResetPeriod::NoReset,
        ]);

        app(BenefitUsageRecorder::class)->useFully($benefit);

        $this->assertEquals(0.0, $benefit->fresh()->remaining());
        $this->assertTrue($benefit->fresh()->is_used);
        $this->assertSame('$0.00', $benefit->fresh()->formattedRemaining());
    }

    #[Test]
    public function ignore_hides_the_benefit_from_tracking(): void
    {
        $benefit = CardBenefit::factory()->create([
            'tracking_mode' => BenefitTrackingMode::Track,
        ]);

        app(BenefitUsageRecorder::class)->ignore($benefit);

        $this->assertTrue($benefit->fresh()->tracking_mode->is(BenefitTrackingMode::Ignore));
    }

    #[Test]
    public function refresher_sets_next_refresh_and_assumes_auto_for_closed_windows(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 20,
            'tracking_mode' => BenefitTrackingMode::Auto,
            'reset_period' => ResetPeriod::CalendarYearly,
            'next_refresh_at' => now()->subDay()->toDateString(),
        ]);

        app(BenefitRefresher::class)->refreshOne($benefit, now());

        $benefit->refresh();
        $this->assertNotNull($benefit->next_refresh_at);
        $this->assertTrue(
            $benefit->usages()->where('source', BenefitUsageSource::AssumedAuto)->exists()
        );
    }

    #[Test]
    public function daily_upkeep_refreshes_benefits(): void
    {
        Http::fake();
        Bus::fake([
            ZeroISB::class,
            DebitIFB::class,
            GuessISB::class,
        ]);

        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::Monthly,
            'next_refresh_at' => null,
        ]);

        DailyUpkeep::dispatchSync();

        $this->assertNotNull($benefit->fresh()->next_refresh_at);
    }

    #[Test]
    public function quarterly_allotment_resets_for_the_next_period(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 100,
            'value_kind' => BenefitValueKind::Currency,
            'reset_period' => ResetPeriod::Quarterly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $this->travelTo('2026-02-15');
        app(BenefitUsageRecorder::class)->record($benefit, 100, now());

        $this->assertEquals(0.0, $benefit->fresh()->remaining());

        $this->travelTo('2026-04-01');

        $this->assertEquals(100.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function monthly_allotment_resets_for_the_next_period(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 15,
            'value_kind' => BenefitValueKind::Currency,
            'reset_period' => ResetPeriod::Monthly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $this->travelTo('2026-02-15');
        app(BenefitUsageRecorder::class)->record($benefit, 15, now());

        $this->assertEquals(0.0, $benefit->fresh()->remaining());

        $this->travelTo('2026-03-01');

        $this->assertEquals(15.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function refresher_assumes_the_standing_partial_amount(): void
    {
        $this->travelTo('2026-04-01');

        $benefit = CardBenefit::factory()->create([
            'value' => 15,
            'value_kind' => BenefitValueKind::Currency,
            'tracking_mode' => BenefitTrackingMode::Auto,
            'auto_assume_amount' => 5,
            'reset_period' => ResetPeriod::Monthly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
            'next_refresh_at' => '2026-04-01',
        ]);

        app(BenefitRefresher::class)->refreshOne($benefit, now());

        $this->assertEquals(5.0, $benefit->usages()->sum('amount'));
        $this->assertEquals(15.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function refresher_does_not_assume_ignored_auto_benefits(): void
    {
        $this->travelTo('2026-04-01');

        $benefit = CardBenefit::factory()->create([
            'value' => 15,
            'value_kind' => BenefitValueKind::Currency,
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'reset_period' => ResetPeriod::Monthly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
            'next_refresh_at' => '2026-04-01',
        ]);

        app(BenefitRefresher::class)->refreshOne($benefit, now());

        $this->assertSame(0, $benefit->usages()->count());
    }

    #[Test]
    public function assume_fully_and_partially_persist_and_credit_an_unused_window(): void
    {
        $benefit = CardBenefit::factory()->create([
            'value' => 300,
            'value_kind' => BenefitValueKind::Currency,
            'tracking_mode' => BenefitTrackingMode::Auto,
        ]);
        $recorder = app(BenefitUsageRecorder::class);

        $recorder->assumePartially($benefit, 50);

        $this->assertEquals(50.0, $benefit->fresh()->auto_assume_amount);
        $this->assertEquals(250.0, $benefit->fresh()->remaining());

        $recorder->assumeFully($benefit->fresh());

        $this->assertNull($benefit->fresh()->auto_assume_amount);
        $this->assertEquals(250.0, $benefit->fresh()->remaining());
    }

    #[Test]
    public function monthly_auto_assumes_only_the_closed_month(): void
    {
        $this->travelTo('2026-04-01');

        $benefit = CardBenefit::factory()->create([
            'value' => 15,
            'value_kind' => BenefitValueKind::Currency,
            'tracking_mode' => BenefitTrackingMode::Auto,
            'reset_period' => ResetPeriod::Monthly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
            'next_refresh_at' => '2026-04-01',
        ]);

        app(BenefitRefresher::class)->refreshOne($benefit, now());

        $this->assertEquals(15.0, $benefit->usages()->sum('amount'));
        $this->assertEquals(15.0, $benefit->fresh()->remaining());
    }
}
