<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Models\Collections\StateDumpCollection;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Uses Apex Charts: https://apexcharts.com/docs/
 * And this filament plugin: https://filamentphp.com/plugins/leandrocfe-apex-charts
 */
class PastStatsChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'PastStatsChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Past Stats';


    protected int|string|array $columnSpan = 4;

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        list(
            $netWorthChart,
            $cardBalanceChart,
            $cardPendingChart,
            $cashPositionChart,
            $pointsChart
            ) = SpentPayingSaving::getStateDumpCharts();

        $pointsColor = '#565ff5';
        $networthColor = '#56f5ec';
        $cashColor = '#56f570';
        $ccColor = '#f55656';
        return [
            'chart' => [
                'type' => 'line',
                'zoom' => [
                    'allowMouseWheelZoom' => false,
                ]
            ],
            'colors' => [$networthColor, $ccColor, $cashColor, $pointsColor],
            'series' => [
                [
                    'name' => 'Net Worth',
                    'data' => self::flipToXY($netWorthChart),
                ],
                [
                    'name' => 'CC Debt',
                    'data' => self::flipToXY(StateDumpCollection::combineArrs($cardBalanceChart, $cardPendingChart)),
                ],
                [
                    'name' => 'Cash Position',
                    'data' => self::flipToXY($cashPositionChart),
                ],
                [
                    'name' => 'Points Balance',
                    'data' => self::flipToXY($pointsChart),
                ],
            ],
            'xaxis' => [
//                'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'type' => 'datetime',
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                [
                    'min' => 0,
                    'title' => [
                        'text' => '$$$',
                        'style' => [
                            'color' => $cashColor
                        ]
                    ],
                    'seriesName' => ['Cash Position','CC Debt','Net Worth'],
                    'labels' => [
                        'style' => [
                            'fontFamily' => 'inherit',
                        ],
                    ],
                ],
                [
                    'min' => 0,
                    'opposite' => true,
                    'title' => [
                        'text' => 'Points Balance',
                        'style' => [
                            'color' => $pointsColor
                        ]
                    ],
                    'labels' => [
                        'style' => [
                            'fontFamily' => 'inherit',
                        ],
                    ],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
            ],
        ];
    }

    public static function flipToXY(array $arr): array
    {
        $arr = collect($arr)->map(fn ($val, $key) => [
            'x' => Carbon::parse($key),
            'y' => round($val, 0),
        ])->toArray();
        return array_values($arr);
    }

}
