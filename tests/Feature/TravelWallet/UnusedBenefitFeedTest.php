<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Models\Activity;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use App\Services\TravelWallet\UnusedBenefitFeed;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnusedBenefitFeedTest extends TestCase
{
    #[Test]
    public function due_excludes_auto_exhausted_and_unrelated_locations(): void
    {
        $tracked = CardBenefit::factory()->create([
            'benefit' => 'Airline credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 200,
            'value_kind' => BenefitValueKind::Currency,
            'next_refresh_at' => now()->addDays(10)->toDateString(),
        ]);

        CardBenefit::factory()->create([
            'benefit' => 'Ignored lounge',
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'value' => 50,
        ]);

        CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
        ]);

        CardBenefit::factory()->create([
            'benefit' => 'Tokyo hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 100,
            'location_city' => 'Tokyo',
        ]);

        Activity::factory()->create([
            'location_name' => 'Paris, France',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $due = app(UnusedBenefitFeed::class)->due(showAllLocations: false);

        $names = $due->pluck('benefit')->all();
        $this->assertContains('Airline credit', $names);
        $this->assertContains('Ignored lounge', $names);
        $this->assertNotContains('Disney+', $names);
        $this->assertNotContains('Tokyo hotel credit', $names);
        $this->assertTrue($due->first()->is($tracked) || $due->contains('id', $tracked->id));
    }

    #[Test]
    public function auto_includes_ignored_and_excludes_tracked(): void
    {
        CardBenefit::factory()->create([
            'benefit' => 'Disney+',
            'tracking_mode' => BenefitTrackingMode::Auto,
            'value' => 14,
        ]);
        CardBenefit::factory()->create([
            'benefit' => 'Ignored lounge',
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'value' => 50,
        ]);
        CardBenefit::factory()->create([
            'benefit' => 'Airline credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 200,
        ]);

        $names = app(UnusedBenefitFeed::class)->auto()->pluck('benefit')->all();

        $this->assertContains('Disney+', $names);
        $this->assertContains('Ignored lounge', $names);
        $this->assertNotContains('Airline credit', $names);
    }

    #[Test]
    public function due_query_drops_a_benefit_after_it_is_exhausted(): void
    {
        $benefit = CardBenefit::factory()->create([
            'benefit' => 'Hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 100,
            'value_kind' => BenefitValueKind::Currency,
        ]);

        $feed = app(UnusedBenefitFeed::class);

        $this->assertTrue($feed->dueQuery()->pluck('id')->contains($benefit->id));

        app(BenefitUsageRecorder::class)->useFully($benefit);

        $this->assertFalse($feed->dueQuery()->pluck('id')->contains($benefit->id));
    }

    #[Test]
    public function location_scoped_benefit_appears_when_an_upcoming_trip_matches(): void
    {
        CardBenefit::factory()->create([
            'benefit' => 'Tokyo hotel credit',
            'tracking_mode' => BenefitTrackingMode::Track,
            'value' => 100,
            'location_city' => 'Tokyo',
        ]);

        Activity::factory()->create([
            'location_name' => 'Tokyo, Japan',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
        ]);

        $due = app(UnusedBenefitFeed::class)->due();

        $this->assertTrue($due->contains(fn (CardBenefit $benefit): bool => $benefit->benefit === 'Tokyo hotel credit'));
    }
}
