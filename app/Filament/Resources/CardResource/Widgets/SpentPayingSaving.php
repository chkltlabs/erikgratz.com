<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Card;
use App\Models\Collections\StateDumpCollection as SDC;
use App\Models\LoanAgainstSavings;
use App\Models\Payment;
use App\Models\Scopes\SumCard;
use App\Models\Scopes\SumPayment;
use App\Models\StateDump;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SpentPayingSaving extends BaseWidget
{
    /**
     * @var int | array<string, ?int> | null
     */
    protected int | array | null $columns = ['@xl' => 4, '!@lg' => 4];

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
            Stat::make($thisMonthName.' CC Due', '$'.$moneyArray[$thisMonthName]['spent']),
            Stat::make($thisMonthName.' Potential Savings', '$'.$moneyArray[$thisMonthName]['potential']),
            Stat::make('Net Worth', '$'.$netWorth)
                ->chart($netWorthChart)
                ->chartColor('success'),

            Stat::make($nextMonthName.' CC Due', '$'.$moneyArray[$nextMonthName]['spent']),
            self::makeUnspentStat($nextMonthName, $moneyArray[$nextMonthName]),
            Stat::make($nextMonthName.' CC Potential Save', '$'.$nextPotential),
            Stat::make($nextMonthName.' CC Projected Net Worth', '$'.($netWorth + $nextPotential)),

            Stat::make($thirdMonthName.' CC Due', '$'.$moneyArray[$thirdMonthName]['spent']),
            self::makeUnspentStat($thirdMonthName, $moneyArray[$thirdMonthName]),
            Stat::make($thirdMonthName.' CC Potential Save', '$'.$thirdPotential),
            Stat::make($thirdMonthName.' CC Projected Net Worth', '$'.($netWorth + $nextPotential + $thirdPotential)),
        ];
    }

    /**
     * @param  array{spent: int|float, planned: int|float, potential: int|float, planned_items?: array<int, array{name: string, amount: float}>}  $monthData
     */
    protected static function makeUnspentStat(string $month, array $monthData): Stat
    {
        $stat = Stat::make($month.' CC Unspent', '$'.$monthData['planned']);

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
        $netWorth = Account::sum('balance') - Card::sum('balance') - Card::sum('pending');

        [$netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart] = self::getStateDumpCharts();

        return [$totalPoints, $pointsChart, $netWorth, $netWorthChart];
    }

    public static function getMoneyData(): array
    {
        $totalMonthIncome = User::sum('monthly_pay');
        $thisMonthCash = Account::forUser(User::erik())
            ->whereType(AccountType::Checking)
            ->sum('balance')
            + ($totalMonthIncome * (now()->day < 15 ? 0.5 : 0));
        // if its before the 15th, add one paycheck ^^^

        $thisMonthName = now()->format('M');
        $nextMonthName = now()->addMonth()->format('M');
        $thirdMonthName = now()->addMonths(2)->format('M');

        $thisMonthSpent = Card::futureDue()->sum('interest_saving_balance')
            + Card::futureDue()->noISBYet()->pipe(new SumCard);
        $loansDue = LoanAgainstSavings::unpaid()->thisMonth()->sum('balance');

        $pastDueISB = Card::pastDue()->sum('interest_saving_balance');

        $nextMonthSpent = $pastDueISB
            + Card::pastDue()->noISBYet()->pipe(new SumCard)
            + Card::futureDue()->pipe(new SumCard)
//            - Card::futureDue()->noISBYet()->sum('interest_saving_balance')
            - $thisMonthSpent;

        $planned = Payment::oneTimeUnpaidDueThisMonth()->pipe(new SumPayment);
        $planned += Payment::yearlyUnpaidDueThisMonth()->pipe(new SumPayment);
        $planned += Payment::monthlyUnpaid()->pipe(new SumPayment);
        $nextPlannedItems = self::plannedItemsForNextMonth();

        $thirdPlanned = Payment::oneTimeUnpaidDueNextMonth()->pipe(new SumPayment);
        $thirdPlanned += Payment::yearlyDueNextMonth()->pipe(new SumPayment);
        $thirdPlanned += Payment::monthly()->pipe(new SumPayment);
        $thirdPlannedItems = self::plannedItemsForThirdMonth();

        $thirdMonthSpent = Card::pastDue()->pipe(new SumCard)
            - $pastDueISB
            + Card::sum('interest_free_balance_payment')
            + LoanAgainstSavings::unpaid()->thirdMonth()->sum('balance');

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
     * @return array<int, array{name: string, amount: float}>
     */
    public static function plannedItemsForNextMonth(): array
    {
        return self::mapPaymentsToPlannedItems(
            Payment::oneTimeUnpaidDueThisMonth()->with('spend')->get()
                ->merge(Payment::yearlyUnpaidDueThisMonth()->with('spend')->get())
                ->merge(Payment::monthlyUnpaid()->with('spend')->get())
                ->unique('id')
        );
    }

    /**
     * @return array<int, array{name: string, amount: float}>
     */
    public static function plannedItemsForThirdMonth(): array
    {
        return self::mapPaymentsToPlannedItems(
            Payment::oneTimeUnpaidDueNextMonth()->with('spend')->get()
                ->merge(Payment::yearlyDueNextMonth()->with('spend')->get())
                ->merge(Payment::monthly()->with('spend')->get())
                ->unique('id')
        );
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<int, array{name: string, amount: float}>
     */
    protected static function mapPaymentsToPlannedItems(Collection $payments): array
    {
        return $payments
            ->filter(fn (Payment $payment): bool => ! $payment->spend?->is_income)
            ->map(fn (Payment $payment): array => [
                'name' => $payment->spend?->name ?? 'Unknown',
                'amount' => (float) $payment->amount,
            ])
            ->values()
            ->all();
    }

    public static function getStateDumpCharts(): array
    {
        return Cache::remember('stateDumps', now()->endOfDay(), function () {
            $stateDumps = StateDump::latest()->get();

            $yearAgo = now()->submonths(6);

            $pointsChart = $stateDumps->sumStatArraysForAllModels(Card::first(), 'points_balance', $yearAgo);

            $cashPositionChart = $stateDumps->sumStatArraysForAllModels(Account::first(), 'balance', $yearAgo);

            $cardBalanceChart = $stateDumps->sumStatArraysForAllModels(Card::first(), 'balance', $yearAgo);
            $cardPendingChart = $stateDumps->sumStatArraysForAllModels(Card::first(), 'pending', $yearAgo);
            $cardBalanceChartNeg = SDC::setArrayNegative($cardBalanceChart);
            $cardPendingChartNeg = SDC::setArrayNegative($cardPendingChart);

            $netWorthChart = SDC::combineArrs(SDC::combineArrs($cashPositionChart, $cardBalanceChartNeg), $cardPendingChartNeg);

            return [$netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart];
        });

    }

    private static function sumTheStuff(Builder|Model $query): int|float
    {
        return $query->sum('balance')
            + $query->sum('pending')
            - $query->sum('interest_free_balance')
            + $query->sum('interest_free_balance_payment');
    }
}
