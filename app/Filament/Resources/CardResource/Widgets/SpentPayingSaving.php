<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Models\Card;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpentPayingSaving extends BaseWidget
{
    protected function getStats(): array
    {
        $reusedQuery = Card::where('due_date', '>=', now()->day);
        $thisMonth = $reusedQuery->sum('interest_saving_balance');
        $nextMonth = (
            Card::where('due_date', '<', now()->day)->sum('interest_saving_balance')
            - $thisMonth
            + $reusedQuery->sum('balance')
            + $reusedQuery->sum('pending')
            - $reusedQuery->sum('interest_free_balance')
            + $reusedQuery->sum('interest_free_balance_payment')
        );
        $potentialSave = User::sum('monthly_pay') - $nextMonth;
        return [
            Stat::make('This Month Unpaid', $thisMonth),
            Stat::make('Next Month Spend', $nextMonth),
            Stat::make('Next Month Save', $potentialSave),
        ];
    }
}
