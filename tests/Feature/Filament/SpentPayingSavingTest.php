<?php

namespace Tests\Feature\Filament;

use App\Enums\Period;
use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Models\Account;
use App\Models\Card;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SpentPayingSavingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function get_stats_returns_twelve_cards_in_expected_order(): void
    {
        Carbon::setTestNow('2026-05-15');

        User::factory()->create(['email' => 'erik@erikgratz.com', 'monthly_pay' => 5000]);
        Card::factory()->create();

        $stats = $this->invokeGetStats();

        $this->assertCount(12, $stats);

        $labels = array_map(fn (Stat $stat): string => (string) $stat->getLabel(), $stats);

        $this->assertSame([
            'Total Points',
            'May CC Due',
            'May Potential Savings',
            'Net Worth',
            'Jun CC Due',
            'Jun CC Unspent',
            'Jun CC Potential Save',
            'Jun CC Projected Net Worth',
            'Jul CC Due',
            'Jul CC Unspent',
            'Jul CC Potential Save',
            'Jul CC Projected Net Worth',
        ], $labels);
    }

    #[Test]
    public function widget_uses_four_column_layout(): void
    {
        $widget = new SpentPayingSaving;

        $reflection = new ReflectionMethod($widget, 'getColumns');
        $reflection->setAccessible(true);

        $this->assertSame(['@xl' => 4, '!@lg' => 4], $reflection->invoke($widget));
    }

    #[Test]
    public function unspent_stat_includes_tooltip_with_planned_spend_line_items(): void
    {
        Carbon::setTestNow('2026-05-15');

        User::factory()->create(['email' => 'erik@erikgratz.com', 'monthly_pay' => 5000]);

        $spend = PeriodicSpend::factory()->create([
            'name' => 'Gym Membership',
            'period' => Period::Monthly,
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $spend->id,
            'amount' => 75,
            'is_paid' => false,
            'paid_on' => now()->setDay(20),
        ]);

        $money = SpentPayingSaving::getMoneyData();
        $jun = now()->addMonth()->format('M');

        $this->assertNotEmpty($money[$jun]['planned_items']);
        $this->assertSame('Gym Membership', $money[$jun]['planned_items'][0]['name']);
        $this->assertSame(75.0, $money[$jun]['planned_items'][0]['amount']);

        $unspentStat = collect($this->invokeGetStats())
            ->first(fn (Stat $stat): bool => (string) $stat->getLabel() === $jun.' CC Unspent');

        $this->assertNotNull($unspentStat);

        $extra = $unspentStat->getExtraAttributes();

        $this->assertArrayHasKey('title', $extra);
        $this->assertStringContainsString('Gym Membership', $extra['title']);
        $this->assertStringContainsString('75.00', $extra['title']);
    }

    #[Test]
    public function projected_net_worth_uses_cumulative_future_savings(): void
    {
        Carbon::setTestNow('2026-05-15');

        User::factory()->create(['email' => 'erik@erikgratz.com', 'monthly_pay' => 8000]);

        Account::factory()->create(['balance' => 10000]);
        Card::factory()->create(['balance' => 2000, 'pending' => 500, 'points_balance' => 0]);

        [, , $netWorth] = SpentPayingSaving::getPointsAndChartData();
        $money = SpentPayingSaving::getMoneyData();

        $jun = now()->addMonth()->format('M');
        $jul = now()->addMonths(2)->format('M');

        $stats = $this->invokeGetStats();
        $valuesByLabel = [];
        foreach ($stats as $stat) {
            $valuesByLabel[(string) $stat->getLabel()] = (string) $stat->getValue();
        }

        $junPotential = $money[$jun]['potential'];
        $julPotential = $money[$jul]['potential'];

        $this->assertSame('$'.($netWorth + $junPotential), $valuesByLabel['Jun CC Projected Net Worth']);
        $this->assertSame('$'.($netWorth + $junPotential + $julPotential), $valuesByLabel['Jul CC Projected Net Worth']);
    }

    /**
     * @return array<Stat>
     */
    protected function invokeGetStats(): array
    {
        $widget = new SpentPayingSaving;

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }
}
