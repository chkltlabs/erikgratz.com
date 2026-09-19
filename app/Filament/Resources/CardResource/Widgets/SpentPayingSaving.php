<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Card;
use App\Models\Collections\StateDumpCollection as SDC;
use App\Models\LoanAgainstSavings;
use App\Models\Payment;
use App\Models\StateDump;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SpentPayingSaving extends BaseWidget
{
    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = ['@xl' => 4, '!@lg' => 4];

    protected function getStats(): array
    {
        $thisMonthName = now()->format('M');
        $nextMonthName = now()->addMonth()->format('M');
        $thirdMonthName = now()->addMonths(2)->format('M');

        $moneyArray = self::getMoneyData();

        [
            $totalPoints,
            $pointsChart,
            $netWorth,
            $netWorthChart,
        ] = self::getPointsAndChartData();

        $nextPotential = $moneyArray[$nextMonthName]['potential'];
        $thirdPotential = $moneyArray[$thirdMonthName]['potential'];

        return [
            Stat::make('Total Points', $totalPoints)
                ->chart($pointsChart)
                ->chartColor('danger'),
            Stat::make($thisMonthName.' CC Due', self::money($moneyArray[$thisMonthName]['spent'])),
            Stat::make($thisMonthName.' Potential Savings', self::money($moneyArray[$thisMonthName]['potential'])),
            Stat::make('Net Worth', self::money($netWorth))
                ->chart($netWorthChart)
                ->chartColor('success'),

            Stat::make($nextMonthName.' CC Due', self::money($moneyArray[$nextMonthName]['spent'])),
            self::makeUnspentStat($nextMonthName, $moneyArray[$nextMonthName]),
            Stat::make($nextMonthName.' CC Potential Save', self::money($nextPotential)),
            Stat::make($nextMonthName.' CC Projected Net Worth', self::money($netWorth + $nextPotential)),

            Stat::make($thirdMonthName.' CC Due', self::money($moneyArray[$thirdMonthName]['spent'])),
            self::makeUnspentStat($thirdMonthName, $moneyArray[$thirdMonthName]),
            Stat::make($thirdMonthName.' CC Potential Save', self::money($thirdPotential)),
            Stat::make($thirdMonthName.' CC Projected Net Worth', self::money($netWorth + $nextPotential + $thirdPotential)),
        ];
    }

    protected static function money(int|float $value): string
    {
        return '$'.number_format((float) $value, 2);
    }

    /**
     * @param  array{spent: int|float, planned: int|float, potential: int|float, planned_items?: array<int, array{name: string, amount: float}>}  $monthData
     */
    protected static function makeUnspentStat(string $month, array $monthData): Stat
    {
        $stat = Stat::make($month.' CC Unspent', self::money($monthData['planned']));

        $items = $monthData['planned_items'] ?? [];

        if ($monthData['planned'] != 0 || $items !== []) {
            $stat->extraAttributes([
                'title' => self::formatUnspentTooltip($items, (float) $monthData['planned']),
            ]);
        }

        return $stat;
    }

    /**
     * @param  array<int, array{name: string, amount: float}>  $items
     */
    public static function formatUnspentTooltip(array $items, float $total): string
    {
        if ($items === []) {
            return $total == 0.0
                ? 'No planned unpaid spends'
                : 'Total: $'.number_format($total, 2);
        }

        $lines = array_map(
            fn (array $item): string => $item['name'].' — $'.number_format($item['amount'], 2),
            $items,
        );

        $lines[] = 'Total: $'.number_format($total, 2);

        return implode("\n", $lines);
    }

    public static function getPointsAndChartData(): array
    {
        $totalPoints = Card::sum('points_balance');
        $netWorth = Account::sumBalanceInUsd() - Card::sum('balance') - Card::sum('pending');

        [$netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart] = self::getStateDumpCharts();

        return [$totalPoints, $pointsChart, $netWorth, $netWorthChart];
    }

    public static function getMoneyData(): array
    {
        $totalMonthIncome = User::sum('monthly_pay');
        $thisMonthCash = Account::sumBalanceInUsd(
            Account::forUser(User::erik())->whereType(AccountType::Checking)
        )
            + ($totalMonthIncome * (now()->day < 15 ? 0.5 : 0));
        // if its before the 15th, add one paycheck ^^^

        $thisMonthName = now()->format('M');
        $nextMonthName = now()->addMonth()->format('M');
        $thirdMonthName = now()->addMonths(2)->format('M');

        $cards = Card::query()->get([
            'id',
            'due_date',
            'balance',
            'pending',
            'interest_saving_balance',
            'interest_free_balance',
            'interest_free_balance_payment',
        ]);

        $todayDay = now()->day;

        $thisMonthSpent = 0.0;
        $nextMonthCardSpent = 0.0;
        $thirdMonthCardSpent = 0.0;
        foreach ($cards as $card) {
            $dues = self::allocateCardDues($card, $todayDay);
            $thisMonthSpent += $dues['this'];
            $nextMonthCardSpent += $dues['next'];
            $thirdMonthCardSpent += $dues['third'];
        }

        $loansDue = (float) LoanAgainstSavings::unpaid()->thisMonth()->sum('balance');
        $nextMonthLoans = (float) LoanAgainstSavings::unpaid()->nextMonth()->sum('balance');
        $nextMonthSpent = $nextMonthCardSpent + $nextMonthLoans;

        $nextPlannedSummary = self::summarizePlannedPayments(self::plannedPaymentsForNextMonth());
        $planned = $nextPlannedSummary['total'];
        $nextPlannedItems = $nextPlannedSummary['items'];

        $thirdPlannedSummary = self::summarizePlannedPayments(self::plannedPaymentsForThirdMonth());
        $thirdPlanned = $thirdPlannedSummary['total'];
        $thirdPlannedItems = $thirdPlannedSummary['items'];

        $thirdMonthLoans = (float) LoanAgainstSavings::unpaid()->thirdMonth()->sum('balance');
        $thirdMonthSpent = $thirdMonthCardSpent + $thirdMonthLoans;

        return [
            $thisMonthName => [
                'spent' => $thisMonthSpent + $loansDue,
                'planned' => 0,
                'planned_items' => [],
                'potential' => $thisMonthCash - $thisMonthSpent - $loansDue,
            ],
            $nextMonthName => [
                'spent' => $nextMonthSpent,
                'planned' => $planned,
                'planned_items' => $nextPlannedItems,
                'potential' => $totalMonthIncome - $nextMonthSpent - $planned,
            ],
            $thirdMonthName => [
                'spent' => $thirdMonthSpent,
                'planned' => $thirdPlanned,
                'planned_items' => $thirdPlannedItems,
                'potential' => $totalMonthIncome - $thirdMonthSpent - $thirdPlanned,
            ],
        ];
    }

    /**
     * Allocate one card's revolving dollars across this / next / third month.
     *
     * Stack(ifb) = balance + pending - ifb + (ifb > 0 && ifbp > 0 ? ifbp : 0)
     *
     * After any month whose due includes IFBP (via ISB or via Stack/IFBP line),
     * project: ifb = max(0, ifb - ifbp). IFBP is assumed to be paid inside any ISB.
     *
     * | State         | This month | Next month            | Third month            |
     * |---------------|------------|-----------------------|------------------------|
     * | Future, ISB≠0 | ISB        | Stack(ifb') - ISB     | IFBP only if ifb'' > 0 |
     * | Future, ISB=0 | Stack(ifb) | IFBP only if ifb' > 0 | IFBP only if ifb'' > 0 |
     * | Past, ISB≠0   | 0*         | ISB                   | Stack(ifb') - ISB      |
     * | Past, ISB=0   | 0          | Stack(ifb)            | IFBP only if ifb' > 0  |
     *
     * * Past + ISB≠0: this month already paid; still apply one IFBP to projected ifb
     *   before next/third (IFBP was inside that ISB payment). Past + ISB≠0 third month
     *   is leftover after next month's ISB (apply IFBP after that ISB before Stack).
     *
     * @return array{this: float, next: float, third: float}
     */
    public static function allocateCardDues(Card $card, int $todayDay): array
    {
        $balance = (float) $card->balance;
        $pending = (float) $card->pending;
        $isb = (float) $card->interest_saving_balance;
        $ifb = (float) $card->interest_free_balance;
        $ifbp = (float) $card->interest_free_balance_payment;
        $past = (int) $card->due_date < $todayDay;

        $thisDue = 0.0;
        $nextDue = 0.0;
        $thirdDue = 0.0;

        if (! $past) {
            if ($isb != 0.0) {
                $thisDue = $isb;
                self::applyIfbp($ifb, $ifbp);

                $nextDue = max(0.0, self::stackAt($ifb, $ifbp, $balance, $pending) - $isb);
                if ($nextDue > 0.0 && $ifb > 0.0 && $ifbp > 0.0) {
                    self::applyIfbp($ifb, $ifbp);
                }

                $thirdDue = self::trailingIfbp($ifb, $ifbp);            } else {
                $thisDue = self::stackAt($ifb, $ifbp, $balance, $pending);
                self::applyIfbp($ifb, $ifbp);

                $nextDue = self::trailingIfbp($ifb, $ifbp);
                if ($nextDue > 0.0) {
                    self::applyIfbp($ifb, $ifbp);
                }

                $thirdDue = self::trailingIfbp($ifb, $ifbp);
            }
        } elseif ($isb != 0.0) {
            // Already paid this cycle; IFBP was inside that ISB payment.
            self::applyIfbp($ifb, $ifbp);

            $nextDue = $isb;
            self::applyIfbp($ifb, $ifbp);

            $thirdDue = max(0.0, self::stackAt($ifb, $ifbp, $balance, $pending) - $isb);
        } else {
            $nextDue = self::stackAt($ifb, $ifbp, $balance, $pending);
            self::applyIfbp($ifb, $ifbp);

            $thirdDue = self::trailingIfbp($ifb, $ifbp);
        }

        return [
            'this' => $thisDue,
            'next' => $nextDue,
            'third' => $thirdDue,
        ];
    }

    protected static function stackAt(float $ifb, float $ifbp, float $balance, float $pending): float
    {
        $ifbpComponent = ($ifb > 0.0 && $ifbp > 0.0) ? $ifbp : 0.0;

        return $balance + $pending - $ifb + $ifbpComponent;
    }

    protected static function trailingIfbp(float $ifb, float $ifbp): float
    {
        return ($ifb > 0.0 && $ifbp > 0.0) ? $ifbp : 0.0;
    }

    protected static function applyIfbp(float &$ifb, float $ifbp): void
    {
        if ($ifbp > 0.0 && $ifb > 0.0) {
            $ifb = max(0.0, $ifb - $ifbp);
        }
    }

    /**
     * Unpaid spends due in the next calendar month via card statement close → due,
     * plus monthlys still unpaid from today forward.
     *
     * @return Collection<int, Payment>
     */
    protected static function plannedPaymentsForNextMonth(): Collection
    {
        $targetMonth = now()->addMonth()->startOfMonth();

        $oneTimeAndYearly = Payment::oneTimeUnpaid()->with(['spend', 'card'])->get()
            ->merge(Payment::yearlyUnpaidAll()->with(['spend', 'card'])->get())
            ->filter(fn (Payment $payment): bool => $payment->cashflowDueFallsInMonth($targetMonth));

        $monthlys = Payment::monthlyUnpaid()->with(['spend', 'card'])->get();

        return $oneTimeAndYearly
            ->merge($monthlys)
            ->unique('id')
            ->values();
    }

    /**
     * Unpaid spends due two months out via card statement close → due, plus all unpaid monthlys.
     *
     * @return Collection<int, Payment>
     */
    protected static function plannedPaymentsForThirdMonth(): Collection
    {
        $targetMonth = now()->addMonths(2)->startOfMonth();

        $oneTimeAndYearly = Payment::oneTimeUnpaid()->with(['spend', 'card'])->get()
            ->merge(Payment::yearlyUnpaidAll()->with(['spend', 'card'])->get())
            ->filter(fn (Payment $payment): bool => $payment->cashflowDueFallsInMonth($targetMonth));

        $monthlys = Payment::monthlyAllUnpaid()->with(['spend', 'card'])->get();

        return $oneTimeAndYearly
            ->merge($monthlys)
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array{total: float, items: array<int, array{name: string, amount: float}>}
     */
    protected static function summarizePlannedPayments(Collection $payments): array
    {
        $items = $payments
            ->filter(fn (Payment $payment): bool => (bool) $payment->spend)
            ->map(function (Payment $payment): array {
                $amount = $payment->amountInUsd();
                if ($payment->spend?->is_income) {
                    $amount *= -1;
                }

                return [
                    'name' => $payment->spend?->name ?? 'Unknown',
                    'amount' => $amount,
                ];
            })
            ->values()
            ->all();

        return [
            'total' => (float) array_sum(array_column($items, 'amount')),
            'items' => $items,
        ];
    }

    public static function getStateDumpCharts(): array
    {
        return Cache::remember('stateDumps', now()->endOfDay(), function () {
            $since = now()->subMonths(6);

            $stateDumps = StateDump::query()
                ->where('created_at', '>=', $since)
                ->orderBy('created_at')
                ->get();

            $cards = Card::query()->get(['id']);
            $accounts = Account::query()->get(['id', 'balance', 'currency']);

            $pointsChart = [];
            $cardBalanceChart = [];
            $cardPendingChart = [];
            $cashPositionChart = [];

            foreach ($stateDumps as $stateDump) {
                $timestamp = $stateDump->created_at->timestamp;
                $data = $stateDump->data ?? [];

                $cardRowsById = self::indexDumpRowsById($data[Card::class] ?? []);
                $accountRowsById = self::indexDumpRowsById($data[Account::class] ?? []);

                $points = 0.0;
                $balances = 0.0;
                $pending = 0.0;
                foreach ($cards as $card) {
                    $row = $cardRowsById[$card->id] ?? null;
                    if ($row === null) {
                        continue;
                    }
                    $points += (float) ($row['points_balance'] ?? 0);
                    $balances += (float) ($row['balance'] ?? 0);
                    $pending += (float) ($row['pending'] ?? 0);
                }

                $cash = 0.0;
                $multipliers = $data['exchange_rates']['multipliers'] ?? [];
                foreach ($accounts as $account) {
                    $row = $accountRowsById[$account->id] ?? null;
                    if ($row === null) {
                        continue;
                    }
                    $balance = (float) ($row['balance'] ?? 0);
                    $currency = strtoupper((string) ($row['currency'] ?? 'USD'));
                    $cash += $balance * (float) ($multipliers[$currency] ?? 1.0);
                }

                $pointsChart[$timestamp] = $points;
                $cardBalanceChart[$timestamp] = $balances;
                $cardPendingChart[$timestamp] = $pending;
                $cashPositionChart[$timestamp] = $cash;
            }

            $cardBalanceChartNeg = SDC::setArrayNegative($cardBalanceChart);
            $cardPendingChartNeg = SDC::setArrayNegative($cardPendingChart);

            $netWorthChart = SDC::combineArrs(
                SDC::combineArrs($cashPositionChart, $cardBalanceChartNeg),
                $cardPendingChartNeg,
            );

            return [$netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int|string, array<string, mixed>>
     */
    protected static function indexDumpRowsById(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! array_key_exists('id', $row)) {
                continue;
            }
            $indexed[$row['id']] = $row;
        }

        return $indexed;
    }
}
