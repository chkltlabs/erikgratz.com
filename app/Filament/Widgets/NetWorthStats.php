<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NetWorthStats extends BaseWidget
{
    protected function getStats(): array
    {
        $actBals = Account::sum('balance');
        $ccBals = Card::sum('balance') + Card::sum('pending');
        $ccDueNext = Card::get()->sum('amountDue');

        return [
            //return [
            //            Stat::make('Current Month Unpaid', '$'.$curM),
            //            Stat::make('Next Month Unpaid', '$'.$nexM),
            //            Stat::make('90 Days Unpaid', '$'.$day90),
            //            Stat::make('180 Days Unpaid', '$'.$day180),
            //        ];

            Stat::make('Bank Balance', '$'.$actBals),
            Stat::make('CC Balance', '$'.$ccBals),
            Stat::make('CC Due Next Mo', '$'.$ccDueNext),
        ];
    }
}
