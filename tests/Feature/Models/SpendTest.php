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

    public function test_actual_spend_and_variance_only_count_confirmed_transactions(): void
    {
        $spend = Spend::factory()->create(['is_income' => false]);
        $spend->payments()->delete();

        // Budgeted: 100
        Payment::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'amount' => 100,
        ]);

        // Transaction 1: Confirmed -60
        \App\Models\SimpleFin\SimpleFinTransaction::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'amount' => -60.00,
            'is_confirmed' => true,
        ]);

        // Transaction 2: Unconfirmed -50
        \App\Models\SimpleFin\SimpleFinTransaction::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'amount' => -50.00,
            'is_confirmed' => false,
        ]);

        $this->assertEquals(60.00, $spend->actual_spend);
        $this->assertEquals(100.00, $spend->total_spend);
        $this->assertEquals(-40.00, $spend->variance); // 60 - 100 = -40 (under budget)
    }

    public function test_display_name_attribute(): void
    {
        $activity = \App\Models\Activity::factory()->create([
            'name' => 'Trip',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-05',
        ]);
        $spend = Spend::factory()->create([
            'name' => 'Food',
            'activity_id' => $activity->id,
        ]);

        $this->assertEquals('Trip • Food • Jan 1st - Jan 5th', $spend->display_name);
    }
}
