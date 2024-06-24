<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use Filament\Support\RawJs;
use Illuminate\Support\Facades\Log;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Uses Apex Charts: https://apexcharts.com/docs/
 * And this filament plugin: https://filamentphp.com/plugins/leandrocfe-apex-charts
 */
class ActivityTimelineChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'activityTimelineChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'ActivityTimelineChart';

    protected int | string | array $columnSpan = 4;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            'datalabels' => [
                'enabled' => false,
                'style' => [
                    'colors' => 'black'
                ],
                'formatter' => 'theFormatFunc',
            ],
            'tooltip' => [
                'enabled' => false,
            ],
            'chart' => [
                'type' => 'rangeBar',
                'height' => 300,
            ],
            'series' => [
                [
                    'data' => [
                        ['x' => 'Code', 'y' => ['1714867200000', '1714888200000']],
//                        ['x' => 'Test', 'y' => ['2024-04-04', '2024-06-05']],
//                        ['x' => 'Validation', 'y' => ['2024-06-04', '2024-08-05']],
//                        ['x' => 'Deployment', 'y' => ['2024-04-04', '2024-08-05']],
                    ],
                ],
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
                'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],

                ],
            ],
            'colors' => ['#f59e0b', '#371BB1'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 15,
                    'horizontal' => true,
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
    {
        // xaxis: {
        //     labels: {
        //         formatter: function (val, timestamp, opts) {
        //             return val + '/24'
        //         }
        //     }
        // },
        // yaxis: {
        //     labels: {
        //         formatter: function (val, index) {
        //             return '$' + val
        //         }
        //     }
        // },
        // tooltip: {
        //     x: {
        //         formatter: function (val) {
        //             return val + '/24'
        //         }
        //     }
        // },
        dataLabels: {
            enabled: true,
            formatter: function (val, opt) {
                console.log(opt)
                return opt.w.globals.labels[opt.dataPointIndex] + ': $' + val
            },
        }
    }
    JS);
    }
}
