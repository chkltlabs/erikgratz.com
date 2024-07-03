<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Collection;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Uses Apex Charts: https://apexcharts.com/docs/
 * And this filament plugin: https://filamentphp.com/plugins/leandrocfe-apex-charts
 */
class ActivityTimelineChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'activityTimelineChart';

    protected static ?string $pollingInterval = null;

    /**
     * Widget Title
     */
    protected static ?string $heading = 'ActivityTimelineChart';

    protected static ?string $loadingIndicator = 'Loading...';

    protected int|string|array $columnSpan = 4;

    protected function getFormSchema(): array
    {
        return [
        ];
    }

    protected static function formatForDataArray(Collection $models): array
    {
        return $models->map(fn ($model) => [
            'x' => null,
            'y' => [
                Carbon::parse($model->start_date ?? $model->spend_for)->valueOf(),
                Carbon::parse($model->end_date ?? $model->spend_for)->valueOf(),
            ],
            'name' => $model->name,
            'amount' => $model->total_spend ?? $model->amount,
            'class' => get_class($model),
            'lo' => $model->start_date ?? $model->spend_for,
            'hi' => $model->end_date ?? $model->spend_for,
            'paid' => $model->paid,
            'unpaid' => $model->unpaid,
            'total_spend' => $model->total_spend,
            'link' => ActivityResource::getUrl('edit', [
                'record' => $model,
            ]),
        ])->toArray();
    }

    protected static function setX(array $data): array
    {
        //leetcode shivers at this ultra-brute-force solution
        //i stand upon the crest of the hill
        //and continue to give not one shit.
        // its O(n)^2 though
        $graphTracker = [[], [], [], [], [], [], [], [], [], [], []];
        foreach ($data as $index => $bar) {
            $lo = Carbon::parse($bar['lo']);
            $hi = Carbon::parse($bar['hi']);
            foreach ($graphTracker as $x => $existingSets) {
                if (empty($existingSets)) {
                    $data[$index]['x'] = "$x";
                    $graphTracker[$x][] = [$lo, $hi];

                    continue 2;
                }
                foreach ($existingSets as $existing) {
                    if (
                        $lo->betweenExcluded($existing[0], $existing[1]) ||
                        $hi->betweenExcluded($existing[0], $existing[1]) ||
                        $existing[0]->betweenExcluded($lo, $hi) ||
                        $existing[1]->betweenExcluded($lo, $hi)
                    ) {
                        continue 2;
                    }
                }
                $data[$index]['x'] = "$x";
                $graphTracker[$x][] = [$lo, $hi];

                continue 2;
            }
        }

        return $data;
    }

    private static function calcSplit(array $entry): int
    {
        $startMS = $entry['y'][0];
        $endMS = $entry['y'][1];
        $span = $endMS - $startMS;
        $total = $entry['paid'] + $entry['unpaid'];
        $percent = $total === 0 ? 0 : ($entry['paid'] / $total);
        $spanPaidPercent = $span * $percent;

        return $startMS + $spanPaidPercent;
    }

    protected static function splitPaidUnpaid(array $data): array
    {
        $dataCopy = $data;

        return [
            array_map(function ($entry) {
                $entry['y'][1] = self::calcSplit($entry);

                if ($entry['y'][1] === $entry['y'][0]) { //removes entry when should be invisible
                    return [];
                }

                return $entry;
            }, $data),
            array_map(function ($entry) {
                $entry['y'][0] = self::calcSplit($entry);

                if ($entry['y'][1] === $entry['y'][0]) { //removes entry when should be invisible
                    return [];
                }

                $entry['y'][0] += 10000000; //avoids visual collisions, 166.667 minutes

                return $entry;
            }, $dataCopy),
        ];
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        [$paid, $unpaid] = self::splitPaidUnpaid(self::setX(self::formatForDataArray(Activity::all())));

        return [
            'datalabels' => [
                'enabled' => false,
                'style' => [
                    'colors' => 'black',
                ],
            ],
            'chart' => [
                'type' => 'rangeBar',
                'height' => 300,
                //                'stacked' => true,
            ],
            'series' => [
                [
                    'name' => 'Paid',
                    'data' => $paid,
                ],
                [
                    'name' => 'Unpaid',
                    'data' => $unpaid,
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
            'colors' => [
                '#32cd32',
                '#b22222',
            ],
            'plotOptions' => [
                'bar' => [
                    //                    'borderRadius' => 5, // split data sets get borders between, doesnt look great
                    'horizontal' => true,
                    'rangeBarGroupRows' => true,
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
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
        chart: {
            events: {
                  dataPointSelection: function(event, chartContext, config) {
                    let dpIndex = config.dataPointIndex
                    let sIndex = config.seriesIndex
                    let clickedEl = config.w.globals.initialSeries[sIndex].data[dpIndex]
                    window.open(clickedEl.link,"_self")
                  }
            }
        },
        tooltip: {
            style: {
                fontSize: '12px',
                fontFamily: undefined
              },
              onDatasetHover: {
                  highlightDataSeries: false,
              },
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                var data = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
                let name = data.name
                let paid = data.paid.toFixed(2);
                let unpaid = data.unpaid.toFixed(2);
                let totalSpend = data.paid + data.unpaid
                let paidPercent = (totalSpend === 0 ? 0 : (paid / totalSpend * 100)).toFixed(2);
                let unpaidPercent = (totalSpend === 0 ? 0 : (unpaid / totalSpend * 100)).toFixed(2);
                return (
                    '<div class="">' +
                        "<span>" +
                            '<span style="color: white;">' +
                                name +
                            "</span>" +
                        "</span>" +
                        "<br>" +
                        "<span>" +
                            '<span style="color: #32cd32;">$' +
                                paid +
                            "</span>" +
                            " / " +
                            '<span style="color: red">$' +
                                unpaid +
                            "</span>" +
                        "</span>" +
                        "<br>" +
                        "<span>" +
                            '<span style="color: #32cd32;">' +
                                paidPercent +
                            "% </span>" +
                            " / " +
                            '<span style="color: red">' +
                                unpaidPercent +
                            "%</span>" +
                        "</span>" +
                    "</div>"
                );
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opt) {
                let index = opt.dataPointIndex
                let data = opt.w.globals.initialSeries[0].data[index]
                return data.name;
            },
        }
    }
    JS
        );
    }
}
