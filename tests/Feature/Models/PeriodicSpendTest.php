<?php

namespace Tests\Feature\Models;

use App\Enums\Period;
use App\Models\PeriodicSpend;
use App\Models\Payment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PeriodicSpendTest extends TestCase
{
    use DatabaseTransactions;

    public function test_accessors_and_totals_for_weekly_period(): void
    {
        $start = Carbon::parse('2024-01-01');
        $end = Carbon::parse('2024-01-21'); // 21 days (3 weeks)
        $ps = PeriodicSpend::factory()->create([
            'period' => Period::Weekly(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);

        // three payments one week apart of $70 -> daily divisor 7 => $10/day
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $ps->id,
            'amount' => 70,
            'paid_on' => '2024-01-01',
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $ps->id,
            'amount' => 70,
            'paid_on' => '2024-01-08',
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $ps->id,
            'amount' => 70,
            'paid_on' => '2024-01-15',
        ]);

        // amount accessor is average payment amount
        $this->assertEquals(70, $ps->amount);
        // totalDays accessor (inclusive)
        $this->assertEquals(21, $ps->total_days);
        // totalSpend attribute repeats each weekly payment across week until next payment
        $this->assertEquals(210, $ps->total_spend);
        // normalizedTotalSpend divides by total days
        $this->assertEqualsWithDelta(10.0, $ps->normalized_total_spend, 0.001);

        // Daily divisor branches
        $this->assertEquals(7, PeriodicSpend::getDailyDivisor(Period::Weekly(), Carbon::parse('2024-01-02')));
        $this->assertEquals(now()->daysInYear, PeriodicSpend::getDailyDivisor(Period::Yearly(), now()));
    }

    public function test_get_daily_chart_and_collapse_to_monthly_and_yearly(): void
    {
        $ps = PeriodicSpend::factory()->create([
            'period' => Period::Monthly(),
            'start_date' => '2024-02-01',
            'end_date' => '2024-03-31',
        ]);
        // two monthly payments of 300 on first day of month
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $ps->id,
            'amount' => 300,
            'paid_on' => '2024-02-01',
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $ps->id,
            'amount' => 300,
            'paid_on' => '2024-03-01',
        ]);

        $daily = $ps->getDailyChartData();
        // February has 29 days in 2024 -> 300/29 per day on 2024-02-01
        $this->assertArrayHasKey('2024-02-01', $daily);
        $this->assertEqualsWithDelta(round(300/29, 2), $daily['2024-02-01']['y'], 0.001);

        $all = PeriodicSpend::getDailyChartDataForAll();
        $this->assertNotEmpty($all);

        $collapsedMonthly = PeriodicSpend::collapseChartDataForPeriod(Period::Monthly(), $all);
        $this->assertNotEmpty($collapsedMonthly);
        // The first day of the first month in data should be a key
        $this->assertArrayHasKey('2024-02-01', $collapsedMonthly);
        // The first day of the second month in data should be a key
        $this->assertArrayHasKey('2024-03-01', $collapsedMonthly);

        $collapsedYearly = PeriodicSpend::collapseChartDataForPeriod(Period::Yearly(), $all);
        $this->assertArrayHasKey('2024-01-01', $collapsedYearly);
    }

    public function test_combine_daily_charts_merges_and_sums_values(): void
    {
        $left = [
            '2024-01-01' => ['y' => 1.0, 'x' => Carbon::parse('2024-01-01')],
            '2024-01-03' => ['y' => 2.0, 'x' => Carbon::parse('2024-01-03')],
        ];
        $right = [
            '2024-01-02' => ['y' => 3.0, 'x' => Carbon::parse('2024-01-02')],
            '2024-01-03' => ['y' => 4.0, 'x' => Carbon::parse('2024-01-03')],
        ];

        $merged = PeriodicSpend::combineDailyCharts($left, $right);
        $this->assertEquals(['2024-01-01','2024-01-02','2024-01-03'], array_keys($merged));
        $this->assertEquals(6.0, $merged['2024-01-03']['y']);
    }
}
