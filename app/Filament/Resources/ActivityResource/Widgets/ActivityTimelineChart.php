<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Models\Activity;
use App\Models\Spend;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Collection;
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

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->default('2024-01-01')
                ->live()
                ->afterStateUpdated(function () {
                    $this->updateOptions();
                }),
            DatePicker::make('date_end')
                ->default('2024-12-31')
                ->live()
                ->afterStateUpdated(function () {
                    $this->updateOptions();
                })
        ];
    }

    protected static function formatForDataArray(Collection $models): array
    {
//        [
//                        [
//                            'x' => '1',
//                            'y' => [
//                                Carbon::parse('2024-05-04')->valueOf(),
//                                Carbon::parse('2024-05-12')->valueOf()
//                            ],
//                            'amount' => 1500,
//                            'name' => 'Barcelona',
//                        ],
//                        [
//                            'x' => '2',
//                            'name' => 'Spain',
//                            'y' => [
//                                Carbon::parse('2023-01-04')->valueOf(),
//                                Carbon::parse('2023-09-05')->valueOf()
//                            ],
//                            'amount' => 6000
//                        ],
//                    ],
        return $models->map(fn ($model) => [
                'x' => null,
                'y' => [
                    Carbon::parse($model->start_date ?? $model->spend_for)->valueOf(),
                    Carbon::parse($model->end_date ?? $model->spend_for)->valueOf()
                ],
                'name' => $model->name,
                'amount' => $model->total_spend ?? $model->amount,
                'class' => get_class($model),
                'lo' => $model->start_date ?? $model->spend_for,
                'hi' => $model->end_date ?? $model->spend_for,
            ])->toArray();
    }

    protected static function setX(array $data): array
    {
        //leetcode shivers at this ultra-brute-force solution
        //i stand upon the crest of the hill
        //and continue to give not one shit.
        // its O(n)^2 though

        $graphTracker = [[], [], [], [], [], [], [], [], [], [], [], ];
//        dump($graphTracker[1]);
        foreach ($data as $index => $bar) {
            $lo = Carbon::parse($bar['lo']);
            $hi = Carbon::parse($bar['hi']);
            foreach ($graphTracker as $x => $existingSets) {
                if(empty($existingSets)) {
//                    dump('Row '.$x.' is empty, '.$bar['name'].' is first entry in row.');
                    $data[$index]['x'] = "$x";
                    $graphTracker[$x][] = [$lo, $hi];
                    continue 2;
                }
                foreach($existingSets as $existing) {

//                    dump($bar['name'] . ' for row ' . $x);
//                    dump('testing if date range ' . $lo->toDateString() . " to " . $hi->toDateString() . " conflicts with " . $existing[0]->toDateString() . ' to ' . $existing[1]->toDateString());
////                    dump('the current $existing '.(empty($existing) ? 'is' : 'is not').' empty');
//                    dump('the current $lo ' . ($lo->betweenExcluded($existing[0], $existing[1]) ? 'is' : 'is not') . ' between $existing');
//                    dump('the current $hi ' . ($hi->betweenExcluded($existing[0], $existing[1]) ? 'is' : 'is not') . ' between $existing');
//                    dump('the current $exlo ' . ($existing[0]->betweenExcluded($lo, $hi) ? 'is' : 'is not') . ' between $hi n $lo');
//                    dump('the current $exhi ' . ($existing[1]->betweenExcluded($lo, $hi) ? 'is' : 'is not') . ' between $hi n $lo');
                    if (
                            $lo->betweenExcluded($existing[0], $existing[1]) ||
                            $hi->betweenExcluded($existing[0], $existing[1]) ||
                            $existing[0]->betweenExcluded($lo, $hi) ||
                            $existing[1]->betweenExcluded($lo, $hi)
                    ) {
//                        dump('A conflict is found in row '.$x.', testing next row');
                        continue 2;
                    }
                }
//                dump('The '.$bar['class']. ' named '. $bar['name']. ' will be added to row '. $x);
                $data[$index]['x'] = "$x";
                $graphTracker[$x][] = [$lo, $hi];
                continue 2;

            }

        }
        return $data;
    }
    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $dateStart = $this->filterFormData['date_start'];
        $dateEnd = $this->filterFormData['date_end'];

        $data = [
            ...self::formatForDataArray(
                Activity::where('start_date', '<=', $dateEnd)
                    ->where('end_date', '>=', $dateStart)
                    ->get()
            ),
            ...self::formatForDataArray(
                Spend::whereActivityId(null)
                    ->whereBetween('spend_for', [$dateStart, $dateEnd])
                    ->get()
            ),
        ];
        $data = self::setX($data);
        return [
            'datalabels' => [
                'enabled' => false,
                'style' => [
                    'colors' => 'black'
                ],
                'formatter' => 'theFormatFunc',
            ],
//            'tooltip' => [
//                'enabled' => false,
//            ],
            'chart' => [
                'type' => 'rangeBar',
                'height' => 300,
            ],
            'series' => [
                [
                    'data' => $data,
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
                'labels' => [
                    'show' => false,
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
        tooltip: {
            y: {
                formatter: function (val, opt) {
                    console.log(val)
                    const month = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
                    let date = new Date(val)
                    return date.getDate() + ' ' + month[date.getMonth()]
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opt) {
                let index = opt.dataPointIndex
                let data = opt.w.globals.initialSeries[0].data[index]
                return data.name + ': $' + data.amount.toFixed(2);
            },
        }
    }
    JS);
    }
}
