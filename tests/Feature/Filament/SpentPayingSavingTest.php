<?php

namespace Tests\Feature\Filament;

use App\Enums\AccountType;
use App\Enums\Period;
use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Models\Account;
use App\Models\Card;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use App\Models\StateDump;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SpentPayingSavingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget('stateDumps');

        parent::tearDown();
    }

    #[Test]
    public function get_stats_returns_twelve_cards_in_expected_order(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 5000]);
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

        $this->ensureErikUser(['monthly_pay' => 5000]);

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

        $erik = $this->ensureErikUser(['monthly_pay' => 8000]);

        Account::factory()->create([
            'user_id' => $erik->id,
            'type' => AccountType::Checking,
            'balance' => 10000,
        ]);
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

    #[Test]
    public function get_money_data_applies_half_paycheck_before_the_fifteenth(): void
    {
        Carbon::setTestNow('2026-05-05');

        $erik = $this->ensureErikUser(['monthly_pay' => 4000]);

        Account::factory()->create([
            'user_id' => $erik->id,
            'type' => AccountType::Checking,
            'balance' => 1000,
        ]);

        Card::factory()->create([
            'due_date' => now()->addDays(5)->day,
            'interest_saving_balance' => 0,
            'balance' => 100,
            'pending' => 0,
            'interest_free_balance' => 0,
            'interest_free_balance_payment' => 0,
        ]);

        $may = now()->format('M');
        $money = SpentPayingSaving::getMoneyData();

        $this->assertEqualsWithDelta(1000 + 2000 - 100, $money[$may]['potential'], 0.01);
    }

    #[Test]
    public function planned_items_for_third_month_include_next_month_one_time_spend(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser();

        $spend = Spend::factory()->bare()->create([
            'name' => 'Annual Insurance',
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 300,
            'is_paid' => false,
            'paid_on' => now()->addMonth()->startOfMonth()->addDays(3),
        ]);

        $items = SpentPayingSaving::plannedItemsForThirdMonth();

        $this->assertCount(1, $items);
        $this->assertSame('Annual Insurance', $items[0]['name']);
        $this->assertSame(300.0, $items[0]['amount']);
    }

    #[Test]
    public function get_state_dump_charts_returns_chart_arrays(): void
    {
        Carbon::setTestNow('2026-05-15');

        $card = Card::factory()->create();
        StateDump::factory()->create([
            'data' => [
                Card::class => [
                    [
                        'id' => $card->id,
                        'balance' => 100,
                        'pending' => 50,
                        'points_balance' => 1000,
                    ],
                ],
            ],
        ]);

        [$netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart] = SpentPayingSaving::getStateDumpCharts();

        $this->assertIsArray($netWorthChart);
        $this->assertIsArray($cardBalanceChart);
        $this->assertIsArray($cardPendingChart);
        $this->assertIsArray($cashPositionChart);
        $this->assertIsArray($pointsChart);
        $this->assertNotEmpty($pointsChart);
        $this->assertSame($card->id, $card->fresh()->id);
    }

    #[Test]
    public function make_unspent_stat_omits_tooltip_when_no_planned_items(): void
    {
        $stat = $this->invokeMakeUnspentStat('Jun', [
            'planned' => 0,
            'planned_items' => [],
        ]);

        $this->assertSame('Jun CC Unspent', (string) $stat->getLabel());
        $this->assertSame('$0', (string) $stat->getValue());
        $this->assertSame([], $stat->getExtraAttributes());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function ensureErikUser(array $attributes = []): User
    {
        $user = User::whereEmail('erik@erikgratz.com')->first();

        if ($user) {
            $user->update($attributes);

            return $user->fresh();
        }

        return User::factory()->create(array_merge([
            'email' => 'erik@erikgratz.com',
        ], $attributes));
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

    /**
     * @param  array{planned: int|float, planned_items?: array<int, array{name: string, amount: float}>}  $monthData
     */
    protected function invokeMakeUnspentStat(string $month, array $monthData): Stat
    {
        $method = new ReflectionMethod(SpentPayingSaving::class, 'makeUnspentStat');
        $method->setAccessible(true);

        return $method->invoke(null, $month, $monthData);
    }
}
