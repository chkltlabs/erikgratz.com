<?php

namespace App\Filament\Widgets;

use App\Models\Spend;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class YearInReview extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'yearInReview';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'YearInReview';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

    public static function getData(): array
    {
        $data = [];
        $date = now()->subYear()->startOfYear();
        while ($date->lt(now()->startOfYear())) {
            $data[] = Spend::whereBetween
        }

    }
    protected function getOptions(): array
    {
        $data = self::getData();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'stacked' => true,
            ],
            'series' => [
                [
                    'name' => 'BasicBarChart',
                    'data' => [7, 10, 13, 15, 18],
                ],
            ],
            'xaxis' => [
                'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#f59e0b'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                ],
            ],
        ];
    }
}
