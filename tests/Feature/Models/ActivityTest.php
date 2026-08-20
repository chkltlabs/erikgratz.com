<?php

namespace Tests\Feature\Models;

use App\Enums\TravelMethod;
use App\Models\Activity;
use App\Models\Payment;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function has_coordinates_requires_both_latitude_and_longitude(): void
    {
        $activity = new Activity(['latitude' => 10.0, 'longitude' => null]);
        $this->assertFalse($activity->hasCoordinates());

        $activity->longitude = 20.0;
        $this->assertTrue($activity->hasCoordinates());
    }

    #[Test]
    public function casts_travel_method_and_exposes_archive_and_day_helpers(): void
    {
        $activity = Activity::factory()->create([
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-10',
            'travel_method' => TravelMethod::Ferry,
            'latitude' => 41.0,
            'longitude' => 29.0,
            'location_name' => 'Istanbul',
        ]);

        $activity->refresh();

        $this->assertInstanceOf(TravelMethod::class, $activity->travel_method);
        $this->assertSame(TravelMethod::Ferry, $activity->travel_method->value);
        $this->assertTrue($activity->archived);
        $this->assertSame(10, (int) $activity->total_days);
        $this->assertArrayHasKey('Jan', $activity->days_by_month);
        $this->assertTrue($activity->hasCoordinates());
        $this->assertTrue($activity->spends()->exists());
        $this->assertInstanceOf(HasMany::class, $activity->redemptions());
    }

    #[Test]
    public function spend_helpers_and_chart_data_work_with_payments(): void
    {
        $activity = Activity::factory()->create([
            'start_date' => '2024-03-01',
            'end_date' => '2024-04-15',
            'name' => 'Spring Trip',
        ]);

        $spend = Spend::factory()->noPayments()->create([
            'activity_id' => $activity->id,
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 100,
            'is_paid' => true,
            'paid_on' => '2024-03-05',
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 40,
            'is_paid' => false,
            'paid_on' => '2024-03-20',
        ]);

        $activity->refresh();

        $this->assertGreaterThan(0, $activity->total_spend);
        $this->assertGreaterThan(0, $activity->normalized_total_spend);
        $this->assertGreaterThan(0, $activity->paid);
        $this->assertGreaterThan(0, $activity->unpaid);
        $this->assertNotEmpty($activity->spend_type_percentages);
        $this->assertNotEmpty($activity->spend_subtype_percentages);
        $this->assertArrayHasKey('Mar', $activity->days_by_month);
        $this->assertArrayHasKey('Apr', $activity->days_by_month);
        $this->assertIsArray($activity->getDailyChartData());
        $this->assertIsArray(Activity::getDailyChartDataForAll());
    }

    #[Test]
    public function percentages_return_empty_when_total_spend_is_zero(): void
    {
        $activity = Activity::factory()->create([
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-02',
        ]);

        foreach ($activity->spends as $spend) {
            $spend->payments()->delete();
            $spend->delete();
        }

        $fresh = $activity->fresh();

        $this->assertSame(0.0, (float) $fresh->total_spend);
        $this->assertSame([], $fresh->spend_type_percentages);
        $this->assertSame([], $fresh->spend_subtype_percentages);
        $this->assertSame([], $fresh->getDailyChartData());
    }
}
