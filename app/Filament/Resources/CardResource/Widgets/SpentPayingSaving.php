<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Models\Card;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SpentPayingSaving extends BaseWidget
{
    protected function getStats(): array
    {
        $futureDueDateCards = Card::where('due_date', '>=', now()->day);
        $pastDueDateCards = Card::where('due_date', '<', now()->day);
        $noISBYetCards = $pastDueDateCards->where('interest_saving_balance', 0);
        $thisMonth = $futureDueDateCards->sum('interest_saving_balance');
        $nextMonth = (
            $pastDueDateCards->sum('interest_saving_balance')
            - $thisMonth
            + $this->sumTheStuff($futureDueDateCards)
            + $this->sumTheStuff($noISBYetCards)
        );
        $potentialSave = User::sum('monthly_pay') - $nextMonth;
        $totalPoints = Card::sum('points_balance');
        return [
            Stat::make('This Month Unpaid', $thisMonth),
            Stat::make('Next Month Spend', $nextMonth),
            Stat::make('Next Month Save', $potentialSave),
            Stat::make('Total Points', $totalPoints),
        ];
    }

    private function sumTheStuff(Builder $query): int|float
    {
        return $query->sum('balance')
            + $query->sum('pending')
            - $query->sum('interest_free_balance')
            + $query->sum('interest_free_balance_payment');
    }
}
