<?php

namespace App\Filament\Widgets;

use App\Models\SimpleFin\SimpleFinTransaction;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SpendingCategoryChart extends ApexChartWidget
{
    protected static ?string $chartId = 'SpendingCategoryChart';
    protected static ?string $heading = 'Spending by Category (This Month)';
    protected int|string|array $columnSpan = 2;

    protected function getOptions(): array
    {
        $transactions = SimpleFinTransaction::query()
            ->where('is_confirmed', true)
            ->where('amount', '<', 0)
            ->whereBetween('posted', [now()->startOfMonth(), now()->endOfMonth()])
            ->with('spend')
            ->get();

        $grouped = $transactions->groupBy(function ($t) {
            return $t->spend?->type?->value ?? 'Other';
        });

        $labels = $grouped->keys()->toArray();
        $totals = $grouped->map(fn ($group) => round(abs($group->sum('amount')), 2))->values()->toArray();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => $totals,
            'labels' => $labels,
            'legend' => [
                'position' => 'bottom',
                'fontFamily' => 'inherit',
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'tooltip' => [
                'y' => [
                    'formatter' => 'function (val) { return "$" + val.toFixed(2) }',
                ],
            ],
        ];
    }
}
