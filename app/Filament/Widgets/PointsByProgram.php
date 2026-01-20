<?php

namespace App\Filament\Widgets;

use App\Enums\PointsProgram;
use App\Models\Card;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PointsByProgram extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $rtn = [];

        foreach (PointsProgram::asSelectArray() as $prog => $label){
            $pointsTotal = Card::where('points_program', $prog)->sum('points_balance');
            if ($pointsTotal == 0) continue;
            $rtn[(-1 * $pointsTotal)] = Stat::make($label, $pointsTotal);
        }
        ksort($rtn);
        return $rtn;
    }
}
