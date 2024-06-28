<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $curMStart = now()->startOfMonth();
        $curMEnd = now()->endOfMonth();
        $nexMStart = now()->addMonth()->startOfMonth();
        $nexMEnd = now()->addMonth()->endOfMonth();

        $day90End = now()->addDays(90);
        $day180End = now()->addDays(180);

        $curM = (string) Payment::whereBetween('paid_on', [$curMStart, $curMEnd])
            ->whereIsPaid(false)
            ->sum('amount');

        $nexM = (string) Payment::whereBetween('paid_on', [$nexMStart, $nexMEnd])
            ->whereIsPaid(false)
            ->sum('amount');

        $day90 = (string) Payment::whereBetween('paid_on', [now(), $day90End])
            ->whereIsPaid(false)
            ->sum('amount');

        $day180 = (string) Payment::whereBetween('paid_on', [now(), $day180End])
            ->whereIsPaid(false)
            ->sum('amount');

        return [
            Stat::make('Current Month Unpaid', '$'.$curM),
            Stat::make('Next Month Unpaid', '$'.$nexM),
            Stat::make('90 Days Unpaid', '$'.$day90),
            Stat::make('180 Days Unpaid', '$'.$day180),
        ];
    }
}
