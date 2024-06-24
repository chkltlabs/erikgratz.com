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
            'borderRadius' => 12,
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
                    'min' => '2021-01-01',
                    'max' => '2025-01-01',
                ]
            ],
            'plugins' => [
                'datalabels' => [
                    'labels' => [
                        'label' => [
//                            'anchor' => 'left',
//                            'align' => 'left',
                            'font' => [
                                'weight' => 'bold'
                            ],
                            'color' => 'grey'
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
                            'x' => ['2022-06-06','2022-07-07'],
                            'y' => 1,
                            'label' => 'Example'

                        ],[
                            'x' => ['2023-05-06','2023-08-07'],
                            'y' => 4,
                            'label' => 'example'

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
