<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Card;
use App\Models\Collections\StateDumpCollection as SDC;
use App\Models\Payment;
use App\Models\Scopes\SumPayment;
use App\Models\StateDump;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SpentPayingSaving extends BaseWidget
{
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
            $netWorthChart
        ] = self::getPointsAndChartData();

        $rtn = [
            Stat::make('Total Points', $totalPoints)
                ->chart($pointsChart)
                ->chartColor('danger'),
            Stat::make('Net Worth', '$'.$netWorth)
                ->chart($netWorthChart)
                ->chartColor('success'),
            Stat::make($thisMonthName.' CC Due & Save',
                '$'.$moneyArray[$thisMonthName]['spent']
                .' / $'.$moneyArray[$thisMonthName]['potential']
            ),
        ];

        foreach ([$nextMonthName, $thirdMonthName] as $month) {
            $rtn[] = Stat::make($month.' CC Due', '$'.$moneyArray[$month]['spent']);
            $rtn[] = Stat::make($month.' CC Unspent', '$'.$moneyArray[$month]['planned']);
            $rtn[] = Stat::make($month.' Save', '$'.$moneyArray[$month]['potential']);
        }

        return $rtn;
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

        $futureDueDateCards = Card::futureDue();
        $pastDueDateCards = Card::pastDue();

        $thisMonthSpent = Card::futureDue()->sum('interest_saving_balance')
            + Card::futureDue()->where('interest_saving_balance', 0)->sum('balance')
            + Card::futureDue()->where('interest_saving_balance', 0)->sum('pending');
        $pastDueISB = $pastDueDateCards->sum('interest_saving_balance');
        $nextMonthSpent = $pastDueISB
            + self::sumTheStuff($pastDueDateCards->where('interest_saving_balance', 0))
            + self::sumTheStuff($futureDueDateCards)
            - $thisMonthSpent;

        $planned = Payment::oneTimeUnpaidDueThisMonth()->pipe(new SumPayment);
        $planned += Payment::yearlyUnpaidDueThisMonth()->pipe(new SumPayment);
        $planned += Payment::monthlyUnpaid()->pipe(new SumPayment);

        $thirdPlanned = Payment::oneTimeUnpaidDueNextMonth()->pipe(new SumPayment);
        $thirdPlanned += Payment::yearlyDueNextMonth()->pipe(new SumPayment);
        $thirdPlanned += Payment::monthly()->pipe(new SumPayment);

        $thirdMonthSpent = self::sumTheStuff(Card::pastDue()) - $pastDueISB;

        return [
            $thisMonthName => [
                'spent' => $thisMonthSpent,
                'planned' => 0,
                'potential' => $thisMonthCash - $thisMonthSpent,
            ],
            $nextMonthName => [
                'spent' => $nextMonthSpent,
                'planned' => $planned,
                'potential' => $totalMonthIncome - $nextMonthSpent - $planned,
            ],
            $thirdMonthName => [
                'spent' => $thirdMonthSpent,
                'planned' => $thirdPlanned,
                'potential' => $totalMonthIncome - $thirdMonthSpent - $thirdPlanned,
            ],
            // ...
        ];
    }

    public static function getStateDumpCharts(): array
    {
        $stateDumps = StateDump::latest()->get();

        $pointsChart = $stateDumps->sumStatArraysForAllModels(Card::first(), 'points_balance');

        $cashPositionChart = $stateDumps->sumStatArraysForAllModels(Account::first(), 'balance');

        $cardBalanceChart = $stateDumps->sumStatArraysForAllModels(Card::first(), 'balance');
        $cardPendingChart = $stateDumps->sumStatArraysForAllModels(Card::first(), 'pending');
        $cardBalanceChartNeg = SDC::setArrayNegative($cardBalanceChart);
        $cardPendingChartNeg = SDC::setArrayNegative($cardPendingChart);

        $netWorthChart = SDC::combineArrs(SDC::combineArrs($cashPositionChart, $cardBalanceChartNeg), $cardPendingChartNeg);

        return [$netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart];
    }

    private static function sumTheStuff(Builder|Model $query): int|float
    {
        return $query->sum('balance')
            + $query->sum('pending')
            - $query->sum('interest_free_balance')
            + $query->sum('interest_free_balance_payment');
    }
}
