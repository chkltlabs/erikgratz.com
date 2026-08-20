<?php

namespace Tests\Feature\Filament;

use App\Enums\AccountType;
use App\Enums\CurrencyCode;
use App\Enums\Period;
use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Models\Account;
use App\Models\Card;
use App\Models\LoanAgainstSavings;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use App\Models\StateDump;
use App\Models\User;
use App\Services\Currency\ExchangeRateService;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SpentPayingSavingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget('stateDumps');
        Cache::flush();

        parent::tearDown();
    }

    #[Test]
    public function net_worth_converts_non_usd_account_balances(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        $this->ensureErikUser(['monthly_pay' => 0]);

        Account::factory()->create([
            'currency' => CurrencyCode::CAD,
            'balance' => 125,
        ]);
        Card::factory()->create(['balance' => 0, 'pending' => 0, 'points_balance' => 0]);

        app(ExchangeRateService::class)->refreshRatesForAccounts();

        [, , $netWorth] = SpentPayingSaving::getPointsAndChartData();

        $this->assertEqualsWithDelta(100.0, $netWorth, 0.01);
    }

    #[Test]
    public function livewire_stats_overview_renders_with_mixed_currency_accounts(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 5000]);
        Account::factory()->create([
            'currency' => CurrencyCode::CAD,
            'balance' => 125,
        ]);
        Card::factory()->create();

        app(ExchangeRateService::class)->refreshRatesForAccounts();

        Livewire::test(SpentPayingSaving::class)->assertSuccessful();
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
            'card_id' => null,
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

        $this->assertSame(
            '$'.number_format($netWorth + $junPotential, 2),
            $valuesByLabel['Jun CC Projected Net Worth'],
        );
        $this->assertSame(
            '$'.number_format($netWorth + $junPotential + $julPotential, 2),
            $valuesByLabel['Jul CC Projected Net Worth'],
        );
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

        // Future + ISB=0 → Stack is due this month
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
        $this->assertEqualsWithDelta(100.0, $money[$may]['spent'], 0.01);
    }

    #[Test]
    public function future_isb_and_no_isb_cards_allocate_across_months(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        Card::factory()->create([
            'due_date' => 20,
            'interest_saving_balance' => 250,
            'balance' => 800,
            'pending' => 50,
            'interest_free_balance' => 0,
            'interest_free_balance_payment' => 0,
        ]);
        Card::factory()->create([
            'due_date' => 25,
            'interest_saving_balance' => 0,
            'balance' => 400,
            'pending' => 0,
            'interest_free_balance' => 0,
            'interest_free_balance_payment' => 0,
        ]);

        $money = SpentPayingSaving::getMoneyData();
        $may = now()->format('M');
        $jun = now()->addMonth()->format('M');
        $jul = now()->addMonths(2)->format('M');

        // this: ISB 250 + Stack 400
        $this->assertEqualsWithDelta(650.0, $money[$may]['spent'], 0.01);
        // next: Stack-ISB for first card = 600; second card done
        $this->assertEqualsWithDelta(600.0, $money[$jun]['spent'], 0.01);
        $this->assertEqualsWithDelta(0.0, $money[$jul]['spent'], 0.01);
    }

    #[Test]
    public function past_isb_and_no_isb_cards_allocate_across_months(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        Card::factory()->create([
            'due_date' => 10,
            'interest_saving_balance' => 100,
            'balance' => 500,
            'pending' => 0,
            'interest_free_balance' => 0,
            'interest_free_balance_payment' => 0,
        ]);
        Card::factory()->create([
            'due_date' => 5,
            'interest_saving_balance' => 0,
            'balance' => 999,
            'pending' => 0,
            'interest_free_balance' => 0,
            'interest_free_balance_payment' => 0,
        ]);

        $money = SpentPayingSaving::getMoneyData();
        $may = now()->format('M');
        $jun = now()->addMonth()->format('M');
        $jul = now()->addMonths(2)->format('M');

        $this->assertEqualsWithDelta(0.0, $money[$may]['spent'], 0.01);
        // next: ISB 100 + Stack 999
        $this->assertEqualsWithDelta(1099.0, $money[$jun]['spent'], 0.01);
        // third: Stack-ISB = 400 for first card only
        $this->assertEqualsWithDelta(400.0, $money[$jul]['spent'], 0.01);
    }

    #[Test]
    public function ifbp_is_inside_isb_and_not_added_on_top_this_month(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        Card::factory()->create([
            'due_date' => 20,
            'interest_saving_balance' => 300,
            'balance' => 1000,
            'pending' => 0,
            'interest_free_balance' => 250,
            'interest_free_balance_payment' => 100,
        ]);

        $dues = SpentPayingSaving::allocateCardDues(Card::first(), 15);

        $this->assertEqualsWithDelta(300.0, $dues['this'], 0.01);
        // After ISB month, IFB projects to 150; next = Stack(150)-300 = (1000-150+100)-300 = 650
        $this->assertEqualsWithDelta(650.0, $dues['next'], 0.01);
    }

    #[Test]
    public function ifbp_runs_off_across_months_until_ifb_is_gone(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        // Future, ISB=0, IFB=250, IFBP=100 → Stack this month, then trailing IFBPs
        Card::factory()->create([
            'due_date' => 20,
            'interest_saving_balance' => 0,
            'balance' => 250,
            'pending' => 0,
            'interest_free_balance' => 250,
            'interest_free_balance_payment' => 100,
        ]);

        $dues = SpentPayingSaving::allocateCardDues(Card::first(), 15);

        // Stack = 250 - 250 + 100 = 100; IFB → 150
        $this->assertEqualsWithDelta(100.0, $dues['this'], 0.01);
        // Trailing IFBP while IFB remains
        $this->assertEqualsWithDelta(100.0, $dues['next'], 0.01);
        $this->assertEqualsWithDelta(100.0, $dues['third'], 0.01);
    }

    #[Test]
    public function past_no_isb_puts_stack_in_next_month_not_third(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        Card::factory()->create([
            'due_date' => 5,
            'interest_saving_balance' => 0,
            'balance' => 400,
            'pending' => 50,
            'interest_free_balance' => 0,
            'interest_free_balance_payment' => 0,
        ]);

        $dues = SpentPayingSaving::allocateCardDues(Card::first(), 15);

        $this->assertEqualsWithDelta(0.0, $dues['this'], 0.01);
        $this->assertEqualsWithDelta(450.0, $dues['next'], 0.01);
        $this->assertEqualsWithDelta(0.0, $dues['third'], 0.01);
    }

    #[Test]
    public function statement_close_assigns_one_time_spend_to_jun_or_jul_boxes(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        $card = Card::factory()->create([
            'statement_date' => 15,
            'due_date' => 5,
        ]);

        $beforeClose = Spend::factory()->bare()->noPayments()->create([
            'name' => 'Before Close',
            'is_income' => false,
        ]);
        $afterClose = Spend::factory()->bare()->noPayments()->create([
            'name' => 'After Close',
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $beforeClose->id,
            'amount' => 110,
            'is_paid' => false,
            'paid_on' => '2026-05-10',
            'card_id' => $card->id,
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $afterClose->id,
            'amount' => 220,
            'is_paid' => false,
            'paid_on' => '2026-05-20',
            'card_id' => $card->id,
        ]);

        $money = SpentPayingSaving::getMoneyData();
        $junItems = collect($money['Jun']['planned_items'])->pluck('name')->all();
        $julItems = collect($money['Jul']['planned_items'])->pluck('name')->all();

        $this->assertContains('Before Close', $junItems);
        $this->assertNotContains('After Close', $junItems);
        $this->assertContains('After Close', $julItems);
        $this->assertNotContains('Before Close', $julItems);
    }

    #[Test]
    public function paid_monthly_is_excluded_from_unspent(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        $spend = PeriodicSpend::factory()->create([
            'name' => 'Already Paid',
            'period' => Period::Monthly,
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $spend->id,
            'amount' => 80,
            'is_paid' => true,
            'paid_on' => now()->setDay(20),
            'card_id' => null,
        ]);

        $money = SpentPayingSaving::getMoneyData();

        $this->assertSame(0.0, $money['Jun']['planned']);
        $this->assertSame([], $money['Jun']['planned_items']);
        $this->assertSame(0.0, $money['Jul']['planned']);
    }

    #[Test]
    public function next_month_loan_is_included_in_second_month_spent(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        LoanAgainstSavings::factory()->create([
            'balance' => 175,
            'is_paid' => false,
            'paid_on' => now()->addMonth()->startOfMonth()->addDays(2),
            'card_id' => null,
        ]);

        $money = SpentPayingSaving::getMoneyData();

        $this->assertEqualsWithDelta(175.0, $money['Jun']['spent'], 0.01);
    }

    #[Test]
    public function null_card_one_time_floats_into_following_month_unspent(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser(['monthly_pay' => 0]);

        $spend = Spend::factory()->bare()->noPayments()->create([
            'name' => 'No Card Float',
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 300,
            'is_paid' => false,
            'paid_on' => '2026-05-12',
            'card_id' => null,
        ]);

        $money = SpentPayingSaving::getMoneyData();

        $this->assertCount(1, $money['Jun']['planned_items']);
        $this->assertSame('No Card Float', $money['Jun']['planned_items'][0]['name']);
        $this->assertSame([], $money['Jul']['planned_items']);
    }

    #[Test]
    public function planned_items_for_third_month_include_next_month_one_time_spend(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser();

        $spend = Spend::factory()->bare()->noPayments()->create([
            'name' => 'Annual Insurance',
            'is_income' => false,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'amount' => 300,
            'is_paid' => false,
            'paid_on' => now()->addMonth()->startOfMonth()->addDays(3),
            'card_id' => null,
        ]);

        $money = SpentPayingSaving::getMoneyData();
        $items = $money[now()->addMonths(2)->format('M')]['planned_items'];

        $this->assertCount(1, $items);
        $this->assertSame('Annual Insurance', $items[0]['name']);
        $this->assertSame(300.0, $items[0]['amount']);
    }

    #[Test]
    public function planned_total_and_tooltip_items_include_income_offsets_consistently(): void
    {
        Carbon::setTestNow('2026-05-15');

        $this->ensureErikUser();

        $expense = PeriodicSpend::factory()->create([
            'name' => 'Expense',
            'period' => Period::Monthly,
            'is_income' => false,
        ]);
        $income = PeriodicSpend::factory()->create([
            'name' => 'Income',
            'period' => Period::Monthly,
            'is_income' => true,
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $expense->id,
            'amount' => 100,
            'is_paid' => false,
            'paid_on' => now()->setDay(20),
            'card_id' => null,
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $income->id,
            'amount' => 40,
            'is_paid' => false,
            'paid_on' => now()->setDay(21),
            'card_id' => null,
        ]);

        $money = SpentPayingSaving::getMoneyData();
        $jun = now()->addMonth()->format('M');

        $this->assertSame(60.0, $money[$jun]['planned']);
        $this->assertCount(2, $money[$jun]['planned_items']);
        $this->assertSame('Expense', $money[$jun]['planned_items'][0]['name']);
        $this->assertSame(100.0, $money[$jun]['planned_items'][0]['amount']);
        $this->assertSame('Income', $money[$jun]['planned_items'][1]['name']);
        $this->assertSame(-40.0, $money[$jun]['planned_items'][1]['amount']);

        $stat = $this->invokeMakeUnspentStat($jun, $money[$jun]);
        $tooltip = (string) ($stat->getExtraAttributes()['title'] ?? '');

        $this->assertStringContainsString('Income — $-40.00', $tooltip);
        $this->assertStringContainsString('Total: $60.00', $tooltip);
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
    public function get_state_dump_charts_excludes_dumps_older_than_six_months(): void
    {
        Carbon::setTestNow('2026-05-15');
        Cache::forget('stateDumps');

        $card = Card::factory()->create();

        $old = StateDump::factory()->create([
            'data' => [
                Card::class => [
                    ['id' => $card->id, 'balance' => 1, 'pending' => 0, 'points_balance' => 1],
                ],
            ],
        ]);
        $old->created_at = now()->subMonths(8);
        $old->save();

        $recent = StateDump::factory()->create([
            'data' => [
                Card::class => [
                    ['id' => $card->id, 'balance' => 100, 'pending' => 0, 'points_balance' => 50],
                ],
            ],
        ]);

        [, $cardBalanceChart, , , $pointsChart] = SpentPayingSaving::getStateDumpCharts();

        $this->assertArrayNotHasKey($old->created_at->timestamp, $cardBalanceChart);
        $this->assertArrayHasKey($recent->created_at->timestamp, $cardBalanceChart);
        $this->assertSame(100.0, $cardBalanceChart[$recent->created_at->timestamp]);
        $this->assertSame(50.0, $pointsChart[$recent->created_at->timestamp]);
    }

    #[Test]
    public function state_dump_uses_custom_collection(): void
    {
        $dump = StateDump::factory()->create(['data' => []]);

        $collection = StateDump::query()->whereKey($dump->id)->get();

        $this->assertInstanceOf(\App\Models\Collections\StateDumpCollection::class, $collection);
    }

    #[Test]
    public function make_unspent_stat_omits_tooltip_when_no_planned_items(): void
    {
        $stat = $this->invokeMakeUnspentStat('Jun', [
            'planned' => 0,
            'planned_items' => [],
        ]);

        $this->assertSame('Jun CC Unspent', (string) $stat->getLabel());
        $this->assertSame('$0.00', (string) $stat->getValue());
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
