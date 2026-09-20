<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Dashboard;

use App\Models\BenefitUsage;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\PointRedemption;
use App\Models\StateDump;
use App\Models\User;
use App\Services\Dashboard\DumpChartData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DumpChartDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-15 12:00:00');
        Cache::forget(DumpChartData::BENEFIT_USAGE_CACHE);
        Cache::forget(DumpChartData::REDEMPTIONS_CACHE);
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget(DumpChartData::BENEFIT_USAGE_CACHE);
        Cache::forget(DumpChartData::REDEMPTIONS_CACHE);

        parent::tearDown();
    }

    #[Test]
    public function benefit_usage_subtracts_annual_fee_on_open_and_each_anniversary(): void
    {
        $card = Card::factory()->create([
            'name' => 'Sapphire Reserve',
            'color' => '#1e3a8a',
            'annual_fee' => 550,
            'date_opened' => '2025-03-15',
        ]);
        $benefit = CardBenefit::factory()->create(['card_id' => $card->id, 'value' => 300]);
        $usage = BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'amount' => 200,
        ]);

        $beforeRenewal = $this->dumpAt('2026-03-14 23:50:00', [
            Card::class => [[
                'id' => $card->id,
                'annual_fee' => 550,
                'date_opened' => '2025-03-15',
            ]],
            BenefitUsage::class => [[
                'id' => $usage->id,
                'card_id' => $card->id,
                'captured' => 200,
                'amount' => 200,
            ]],
        ]);
        $onRenewal = $this->dumpAt('2026-03-15 23:50:00', [
            Card::class => [[
                'id' => $card->id,
                'annual_fee' => 550,
                'date_opened' => '2025-03-15',
            ]],
            BenefitUsage::class => [[
                'id' => $usage->id,
                'card_id' => $card->id,
                'captured' => 200,
                'amount' => 200,
            ]],
        ]);

        $chart = DumpChartData::benefitUsageVsFees();

        $this->assertSame(-350.0, $chart['total'][$beforeRenewal->created_at->timestamp]);
        $this->assertSame(-900.0, $chart['total'][$onRenewal->created_at->timestamp]);
        $this->assertSame('Sapphire Reserve', $chart['cards'][0]['name']);
        $this->assertSame(-350.0, $chart['cards'][0]['values'][$beforeRenewal->created_at->timestamp]);
        $this->assertSame(-900.0, $chart['cards'][0]['values'][$onRenewal->created_at->timestamp]);
    }

    #[Test]
    public function benefit_usage_totals_multiple_cards(): void
    {
        $first = Card::factory()->create(['name' => 'Card A', 'annual_fee' => 0]);
        $second = Card::factory()->create(['name' => 'Card B', 'annual_fee' => 0]);

        $dump = $this->dumpAt('2026-04-01 23:50:00', [
            Card::class => [
                ['id' => $first->id, 'annual_fee' => 0, 'date_opened' => '2024-01-01'],
                ['id' => $second->id, 'annual_fee' => 0, 'date_opened' => '2024-01-01'],
            ],
            BenefitUsage::class => [
                ['id' => 1, 'card_id' => $first->id, 'captured' => 100],
                ['id' => 2, 'card_id' => $second->id, 'captured' => 40],
            ],
        ]);

        $chart = DumpChartData::benefitUsageVsFees();

        $this->assertSame(140.0, $chart['total'][$dump->created_at->timestamp]);
    }

    #[Test]
    public function redemption_value_totals_cash_and_cents_per_point(): void
    {
        $dump = $this->dumpAt('2026-04-01 23:50:00', [
            PointRedemption::class => [
                [
                    'id' => 1,
                    'points_spent' => 50000,
                    'money_spent' => 100,
                    'cash_value' => 750,
                ],
                [
                    'id' => 2,
                    'points_spent' => 10000,
                    'money_spent' => 20,
                    'cash_value' => 150,
                ],
            ],
        ]);

        $chart = DumpChartData::redemptionValue();
        $timestamp = $dump->created_at->timestamp;

        $this->assertSame(900.0, $chart['cash_value'][$timestamp]);
        $this->assertSame(120.0, $chart['money_spent'][$timestamp]);
        $this->assertSame(60000.0, $chart['points_spent'][$timestamp]);
        $this->assertSame(780.0, $chart['money_saved'][$timestamp]);
        $this->assertEqualsWithDelta(1.3, $chart['cents_per_point'][$timestamp], 0.0001);
        $this->assertSame('60,000 pts / $780.00 saved = 1.30¢/pt', $chart['breakdown'][0]['label']);
    }

    #[Test]
    public function redemption_value_backfills_missing_dump_rows_from_paid_on(): void
    {
        PointRedemption::factory()->create([
            'paid_on' => '2026-03-01',
            'points_spent' => 40000,
            'money_spent' => 50,
            'cash_value' => 600,
        ]);
        PointRedemption::factory()->create([
            'paid_on' => '2026-04-10',
            'points_spent' => 20000,
            'money_spent' => 70,
            'cash_value' => 300,
        ]);
        PointRedemption::factory()->create([
            'paid_on' => '2026-05-20',
            'points_spent' => 99999,
            'money_spent' => 999,
            'cash_value' => 9999,
        ]);

        $march = $this->dumpAt('2026-03-15 23:50:00', []);
        $april = $this->dumpAt('2026-04-15 23:50:00', []);

        $chart = DumpChartData::redemptionValue();

        $this->assertSame(600.0, $chart['cash_value'][$march->created_at->timestamp]);
        $this->assertSame(50.0, $chart['money_spent'][$march->created_at->timestamp]);
        $this->assertSame(40000.0, $chart['points_spent'][$march->created_at->timestamp]);

        $this->assertSame(900.0, $chart['cash_value'][$april->created_at->timestamp]);
        $this->assertSame(120.0, $chart['money_spent'][$april->created_at->timestamp]);
        $this->assertSame(60000.0, $chart['points_spent'][$april->created_at->timestamp]);
        $this->assertSame('60,000 pts / $780.00 saved = 1.30¢/pt', $chart['breakdown'][1]['label']);
    }

    #[Test]
    public function redemption_value_uses_dump_snapshot_when_present(): void
    {
        PointRedemption::factory()->create([
            'paid_on' => '2026-03-01',
            'points_spent' => 40000,
            'money_spent' => 50,
            'cash_value' => 600,
        ]);

        $dump = $this->dumpAt('2026-04-01 23:50:00', [
            PointRedemption::class => [[
                'id' => 1,
                'points_spent' => 1000,
                'money_spent' => 10,
                'cash_value' => 20,
            ]],
        ]);

        $chart = DumpChartData::redemptionValue();

        $this->assertSame(20.0, $chart['cash_value'][$dump->created_at->timestamp]);
        $this->assertSame(10.0, $chart['money_spent'][$dump->created_at->timestamp]);
        $this->assertSame(1000.0, $chart['points_spent'][$dump->created_at->timestamp]);
    }

    #[Test]
    public function annual_fees_through_counts_open_and_anniversaries(): void
    {
        $this->assertSame(550.0, DumpChartData::annualFeesThrough('2024-03-15', 550, Carbon::parse('2025-03-14')));
        $this->assertSame(1100.0, DumpChartData::annualFeesThrough('2024-03-15', 550, Carbon::parse('2025-03-15')));
        $this->assertSame(0.0, DumpChartData::annualFeesThrough(null, 550, now()));
        $this->assertSame(0.0, DumpChartData::annualFeesThrough('2024-03-15', 0, now()));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dumpAt(string $at, array $data): StateDump
    {
        $dump = StateDump::factory()->create(['data' => $data]);
        $dump->created_at = Carbon::parse($at);
        $dump->save();

        return $dump->fresh();
    }
}
