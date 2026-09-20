<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DumpChartData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class BenefitUsageChart extends ApexChartWidget
{
    protected static ?string $chartId = 'BenefitUsageChart';

    protected static ?string $heading = 'Benefit usage vs fees';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $data = DumpChartData::benefitUsageVsFees();

        $series = [
            [
                'name' => 'Total',
                'data' => DumpChartData::toXy($data['total']),
            ],
        ];
        $colors = ['#111827'];

        foreach ($data['cards'] as $card) {
            $series[] = [
                'name' => $card['name'],
                'data' => DumpChartData::toXy($card['values']),
            ];
            $colors[] = $card['color'];
        }

        $strokeWidths = array_fill(0, count($series), 2);
        $strokeWidths[0] = 3;

        return [
            'chart' => [
                'type' => 'line',
                'zoom' => [
                    'allowMouseWheelZoom' => false,
                ],
            ],
            'colors' => $colors,
            'series' => $series,
            'stroke' => [
                'curve' => 'smooth',
                'width' => $strokeWidths,
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
                'title' => [
                    'text' => 'Net vs fees',
                ],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
        ];
    }
}
