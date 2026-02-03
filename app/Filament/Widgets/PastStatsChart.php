<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Models\Collections\StateDumpCollection;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Uses Apex Charts: https://apexcharts.com/docs/
 * And this filament plugin: https://filamentphp.com/plugins/leandrocfe-apex-charts
 */
class PastStatsChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'PastStatsChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Past Stats';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        [
            $netWorthChart,
            $cardBalanceChart,
            $cardPendingChart,
            $cashPositionChart,
            $pointsChart
        ] = SpentPayingSaving::getStateDumpCharts();

        $pointsColor = '#565ff5';
        $networthColor = '#56f5ec';
        $cashColor = '#56f570';
        $ccColor = '#f55656';

        return [
            'chart' => [
                'type' => 'line',
                'zoom' => [
                    'allowMouseWheelZoom' => false,
                ],
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
                            'color' => $cashColor,
                        ],
                    ],
                    'seriesName' => ['Cash Position', 'CC Debt', 'Net Worth'],
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
                            'color' => $pointsColor,
                        ],
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

    public function getOldSchemaState(string $statePath): mixed
    {
        // TODO: Implement getOldSchemaState() method.
    }

    public function getSchema(string $name): ?Schema
    {
        // TODO: Implement getSchema() method.
    }

    public function currentlyValidatingSchema(?Schema $schema): void
    {
        // TODO: Implement currentlyValidatingSchema() method.
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        // TODO: Implement getDefaultTestingSchemaName() method.
    }
}
