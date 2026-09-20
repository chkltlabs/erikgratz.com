<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DumpChartData;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RedemptionValueChart extends ApexChartWidget
{
    protected static ?string $chartId = 'RedemptionValueChart';

    protected static ?string $heading = 'Redemption value';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $data = DumpChartData::redemptionValue();

        $cashValuePoints = [];
        foreach (DumpChartData::toXy($data['cash_value']) as $index => $point) {
            $cashValuePoints[] = array_merge($point, $data['breakdown'][$index] ?? []);
        }

        return [
            'chart' => [
                'type' => 'rangeArea',
                'zoom' => [
                    'allowMouseWheelZoom' => false,
                ],
            ],
            'colors' => ['#86efac', '#0f766e', '#dc2626'],
            'series' => [
                [
                    'name' => 'Money saved',
                    'type' => 'rangeArea',
                    'data' => DumpChartData::toRangeXy($data['money_spent'], $data['cash_value']),
                ],
                [
                    'name' => 'Cash value',
                    'type' => 'line',
                    'data' => $cashValuePoints,
                ],
                [
                    'name' => 'Cash spent',
                    'type' => 'line',
                    'data' => DumpChartData::toXy($data['money_spent']),
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [0, 2, 2],
            ],
            'fill' => [
                'opacity' => [0.35, 1, 1],
            ],
            'xaxis' => [
                'type' => 'datetime',
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'min' => 0,
                'title' => [
                    'text' => '$$$',
                ],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            tooltip: {
                shared: true,
                custom: function({ dataPointIndex, w }) {
                    var point = w.globals.initialSeries[1].data[dataPointIndex];
                    if (!point) {
                        return '';
                    }

                    return `<div class='p-2 text-sm'>
                        <div>Cash value: $${Number(point.cash_value).toFixed(2)}</div>
                        <div>Cash spent: $${Number(point.money_spent).toFixed(2)}</div>
                        <div>${point.label}</div>
                    </div>`;
                }
            }
        }
        JS);
    }
}
