<?php

namespace App\Filament\Resources\PeriodicSpendResource\Widgets;

use App\Enums\Period;
use App\Models\Activity;
use App\Models\PeriodicSpend;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Uses Apex Charts: https://apexcharts.com/docs/
 * And this filament plugin: https://filamentphp.com/plugins/leandrocfe-apex-charts
 */
class SpendOverTimeChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'spendOverTimeChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Spend Over Time';

    protected int|string|array $columnSpan = 4;

    protected ?string $pollingInterval = null;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $data = PeriodicSpend::combineDailyCharts(
            PeriodicSpend::getDailyChartDataForAll(),
            Activity::getDailyChartDataForAll()
        );

        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
                'zoom' => [
                    'allowMouseWheelZoom' => false,
                ],
            ],
            'series' => [
                //                [
                //                    'name' => 'SpendOverTimeChart',
                //                    'data' => [
                //                        [
                //                            'x' => now()->addDays(rand(1,4)),
                //                            'y' => 2025
                //                        ],
                //                        [
                //                            'x' => now()->addDays(rand(5,15)),
                //                            'y' => 4545
                //                        ],
                //                        [
                //                            'x' => now()->addDays(rand(15,45)),
                //                            'y' => 345
                //                        ],
                //                        [
                //                            'x' => now()->addDays(rand(45,60)),
                //                            'y' => 1244
                //                        ],
                //                    ],
                //                ],
                [
                    'name' => 'DailySpend',
                    'data' => array_values(
                        PeriodicSpend::collapseChartDataForPeriod(
                            Period::Daily(),
                            $data
                        )
                    ),
                ],
                [
                    'name' => 'WeeklySpend',
                    'data' => array_values(
                        PeriodicSpend::collapseChartDataForPeriod(
                            Period::Weekly(),
                            $data
                        )
                    ),
                ],
                [
                    'name' => 'MonthlySpend',
                    'data' => array_values(
                        PeriodicSpend::collapseChartDataForPeriod(
                            Period::Monthly(),
                            $data
                        )
                    ),
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
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#00FF00', '#FFFF00',  '#FF0000'],
            'stroke' => [
                'curve' => 'smooth',
            ],
        ];
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
