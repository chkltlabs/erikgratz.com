<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Models\Account;
use App\Models\Card;
use App\Models\Collections\StateDumpCollection as SDC;
use App\Models\Payment;
use App\Models\StateDump;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SpentPayingSaving extends BaseWidget
{
    protected function getStats(): array
    {
        list($thisMonth, $nextMonth, $planned, $potentialSave, $totalPoints, $pointsChart, $netWorth, $netWorthChart) = self::getData();
        return [
            Stat::make('This Month CC Due', '$'.$thisMonth),
            Stat::make('Next Month Spent', '$'.$nextMonth),
            Stat::make('Next Month Planned Unspent', '$'.$planned),
            Stat::make('Next Month Save', '$'.$potentialSave),
            Stat::make('Total Points', $totalPoints)
                ->chart($pointsChart)
                ->chartColor('danger'),
            Stat::make('Net Worth', '$'.$netWorth)
                ->chart($netWorthChart)
                ->chartColor('success'),

        ];
    }

    public static function getData(): array
    {
        $futureDueDateCards = Card::where('due_date', '>=', now()->day);
        $pastDueDateCards = Card::where('due_date', '<', now()->day);
        $noISBYetCards = Card::where('due_date', '<', now()->day)->where('interest_saving_balance', 0);
        $thisMonth = $futureDueDateCards->sum('interest_saving_balance');
        $nextMonth = (
            $pastDueDateCards->sum('interest_saving_balance')
            - $thisMonth
            + self::sumTheStuff($futureDueDateCards)
            + self::sumTheStuff($noISBYetCards)
        );
        $planned = Payment::query()
            ->whereMonth('paid_on', now()->month)
            ->whereYear('paid_on', now()->year)
            ->where('is_paid', false)
            ->sum('amount');
        $potentialSave = self::calculatePotentialSave($nextMonth, $thisMonth, $planned);
        $totalPoints = Card::sum('points_balance');
        $netWorth = Account::sum('balance') - Card::sum('balance') - Card::sum('pending');

        list($netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart) = self::getStateDumpCharts();

        return [$thisMonth, $nextMonth, $planned, $potentialSave, $totalPoints, $pointsChart, $netWorth, $netWorthChart];
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

        return array($netWorthChart, $cardBalanceChart, $cardPendingChart, $cashPositionChart, $pointsChart);
    }

    private static function sumTheStuff(Builder $query): int|float
    {
        return $query->sum('balance')
            + $query->sum('pending')
            - $query->sum('interest_free_balance')
            + $query->sum('interest_free_balance_payment');
    }

    private static function fixforWeekends(int $day): Carbon
    {
        $exact = now()->setDay($day);
        return $exact->isSunday()
            ? $exact->subDays(2)
            : ( $exact->isSaturday()
                ? $exact->subDay()
                : $exact
            );
    }

    private static function calculatePotentialSave(int|float $nextMonth, int|float $thisMonthYTBPaid, int|float $planned): int|float
    {
        /**
         * Let's think this through
         *
         * we have $nextMonth, representing the spend we owe in the month after now()
         * and $thisMonthYTBPaid, representing payments for the current month that have not been debited
         *
         * we need to add:
         * my current account bal,
         * any payments marked as income with dates between start of this month & end of next month,
         * total pay for next month ( Aug 30 + Sep 15 = whole month ),
         * any paychecks ytb paid this month,
         *      (add 15th pmt if before 15th [or preceding fri],
         *       sub 30th pmt if after 30th [or preceding fri] and month is not over)
         *
         * then subtract:
         * this month unpaid cc bills,
         * this month FINISHED spends,
         * AND this month PLANNED spends
         *
         * return result
         */
        $firstPaymentDate = self::fixforWeekends(15)->day;
        $secondPaymentDate = self::fixforWeekends(now()->endOfMonth()->day)->day;
        $today = now()->day;
        $totalPayForNextMonth = User::sum('monthly_pay');
        if($today >= $secondPaymentDate) {
            $totalPayForNextMonth /= 2;
        }elseif ($today < $firstPaymentDate) {
            $totalPayForNextMonth *= 1.5;
        }

        $erikAccountBal = Account::whereType('Checking')
            ->forUser('Erik')
            ->sum('balance');
        $incomePaymentsInRange = Payment::where('is_paid', false)
            ->whereBetween('paid_on', [now()->startOfMonth(), now()->addMonth()->endOfMonth()])
            ->whereRelation('spend','is_income', '=', true)
            ->sum('amount');

        return $erikAccountBal + $incomePaymentsInRange + $totalPayForNextMonth - $thisMonthYTBPaid - $nextMonth - $planned;
    }
}
