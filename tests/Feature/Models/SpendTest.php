<?php

namespace Tests\Feature\Models;

use App\Models\Activity;
use App\Models\Payment;
use App\Models\Spend;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpendTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_daily_chart_data_aggregates_by_day(): void
    {
        $activity = Activity::factory()->create(['start_date' => '2024-01-01']);
        $spend = Spend::factory()->create(['activity_id' => $activity->id]);

        // Two payments on same day and one later, one with null paid_on should fall back to activity start_date
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 10,
            'paid_on' => '2024-01-05',
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 15,
            'paid_on' => '2024-01-05',
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 20,
            'paid_on' => null,
        ]);

        $chart = $spend->getDailyChartData();
        $this->assertEquals(25.0, $chart['2024-01-05']['y']);
        $this->assertEquals(20.0, $chart['2024-01-01']['y']);
    }
}
