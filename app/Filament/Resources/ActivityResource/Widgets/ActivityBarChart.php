<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ActivityBarChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getOptions(): array|RawJs|null
    {
        return [
            'indexAxis' => 'y',
            'borderSkipped' => false,
            'borderRadius' => 25,
            'responsive' => true,
            'ticks.source' => 'data',
            'grid' => [
                'lineWidth' => 2
            ],
            'scales' => [
                'x' => [
                    'type' => 'time',
                    'time' => [
                        'unit' => 'month'
                    ],
                    'min' => '2023-01-01',
                ]
            ],
            'plugins' => [
                'datalabels' => [
                    'labels' => [
                        'label' => [
                            'color' => 'green'
                        ]
                    ]
                ]
            ],
        ];
    }

    protected int | string | array $columnSpan = 3;
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Activities',
                    'backgroundColor' => ['#add8e6','#ffcccb'],
                    'borderColor' => ['#add8e6','#ffcccb'],
                    'data' => [
                        [
                            'x' => ['2024-06-06','2024-07-07'],
                            'y' => 1,
                            'name' => 'spoot'

                        ],[
                            'x' => ['2023-05-06','2023-08-07'],
                            'y' => 4,
                            'label' => 'spoot'

                        ]
                    ],

                ],
            ],
            'labels' => [1,2,3,4,5,6,7,8],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
