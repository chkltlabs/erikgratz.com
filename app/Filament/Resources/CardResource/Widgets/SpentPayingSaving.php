<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Models\Account;
use App\Models\Card;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SpentPayingSaving extends BaseWidget
{
    protected function getStats(): array
    {
        list($thisMonth, $nextMonth, $potentialSave, $totalPoints) = self::getData();
        return [
            Stat::make('This Month Unpaid', '$'.$thisMonth),
            Stat::make('Next Month Spend', '$'.$nextMonth),
            Stat::make('Next Month Save', '$'.$potentialSave),
            Stat::make('Total Points', $totalPoints),
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
        $potentialSave = User::sum('monthly_pay')
            + Payment::where('is_paid', false)
                ->whereBetween('paid_on', [now()->addMonth()->startOfMonth(), now()->addMonth()->endOfMonth()])
                ->whereRelation('spend','is_income', '=', true)
                ->sum('amount')
            + Account::whereType('Checking')
                ->forUser('Erik')
                ->sum('balance')
            + (now()->day <= 15 ? User::sum('monthly_pay') / 2 : 0) //if its before the halfway point in the month, we get another paycheck before next month
            - $nextMonth;
        $totalPoints = Card::sum('points_balance');

        return [$thisMonth, $nextMonth, $potentialSave, $totalPoints];
    }

    private static function sumTheStuff(Builder $query): int|float
    {
        return $query->sum('balance')
            + $query->sum('pending')
            - $query->sum('interest_free_balance')
            + $query->sum('interest_free_balance_payment');
    }
}
