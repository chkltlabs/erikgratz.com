<?php

namespace App\Filament\Widgets;

use App\Models\SimpleFin\SimpleFinTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SpendingTrendsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'SpendingTrendsChart';
    protected static ?string $heading = 'Monthly Spending Trends';
    protected int|string|array $columnSpan = 2;

    protected function getOptions(): array
    {
        $data = SimpleFinTransaction::query()
            ->where('is_confirmed', true)
            ->where('amount', '<', 0)
            ->where('posted', '>=', now()->subMonths(6)->startOfMonth())
            ->select([
                DB::raw("DATE_FORMAT(posted, '%Y-%m') as month"),
                DB::raw("ABS(SUM(amount)) as total")
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = $data->pluck('month')->map(fn ($m) => Carbon::parse($m . '-01')->format('M Y'))->toArray();
        $totals = $data->pluck('total')->toArray();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'Confirmed Spending',
                    'data' => $totals,
                ],
            ],
            'xaxis' => [
                'categories' => $months,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'formatter' => 'function (val) { return "$" + val.toFixed(0) }',
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#f59e0b'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'horizontal' => false,
                ],
            ],
        ];
    }
}
