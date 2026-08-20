<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\CardResource;
use App\Models\Activity;
use App\Models\Card;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Collection;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Uses Apex Charts: https://apexcharts.com/docs/
 * And this filament plugin: https://filamentphp.com/plugins/leandrocfe-apex-charts
 */
class ActivityTimelineChart extends ApexChartWidget
{
    private const MAX_ROWS = 11;

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'activityTimelineChart';

    protected ?string $pollingInterval = null;

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Timeline';

    protected static ?string $loadingIndicator = 'Loading...';

    protected int|string|array $columnSpan = 4;

    protected function getFormSchema(): array
    {
        return [
        ];
    }

    protected static function formatCardsForDataArray(Collection $models): array
    {
        return $models->map(fn ($model) => [
            'x' => 'card',
            'y' => [
                Carbon::parse($model->date_opened)->valueOf(),
                Carbon::parse($model->date_opened)->modify($model->points_bonus_period ?? '+1 Day')->valueOf(),
            ],
            'name' => $model->name,
            'amount' => $model->points_bonus_spend,
            'class' => get_class($model),
            'lo' => Carbon::parse($model->date_opened)->valueOf(),
            'hi' => Carbon::parse($model->date_opened)->modify($model->points_bonus_period ?? '+1 Day')->valueOf(),
            'paid' => $model->balance + $model->pending + $model->paidPaymentTotal,
            'unpaid' => $model->plannedPaymentTotal,
            'total_spend' => $model->points_bonus_spend,
            'link' => CardResource::getUrl('index', [
                'record' => $model,
            ]),
        ])->toArray();
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
        $unplaced = array_keys($data);

        for ($row = 0; $row < self::MAX_ROWS && $unplaced !== []; $row++) {
            $chosen = self::bestFitRowIndexes($data, $unplaced);
            $chosenLookup = array_flip($chosen);

            foreach ($chosen as $index) {
                $data[$index]['x'] = (string) $row;
            }

            $unplaced = array_values(array_filter(
                $unplaced,
                fn (int $index): bool => ! isset($chosenLookup[$index]),
            ));
        }

        return $data;
    }

    /**
     * Pick the non-overlapping subset of unplaced bars that covers the most total time
     * (weighted interval scheduling). Ties prefer the excluding branch so earlier-ending
     * (and thus earlier-starting among equal-duration) bars win the lower row.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @param  list<int>  $unplaced
     * @return list<int>
     */
    private static function bestFitRowIndexes(array $data, array $unplaced): array
    {
        $candidates = [];
        foreach ($unplaced as $index) {
            $start = (int) $data[$index]['y'][0];
            $end = (int) $data[$index]['y'][1];
            $candidates[] = [
                'index' => $index,
                'start' => $start,
                'end' => $end,
                'weight' => max(0, $end - $start) + 1,
            ];
        }

        usort($candidates, function (array $a, array $b): int {
            if ($a['end'] !== $b['end']) {
                return $a['end'] <=> $b['end'];
            }

            return $a['start'] <=> $b['start'];
        });

        $n = count($candidates);
        if ($n === 0) {
            return [];
        }

        $prev = array_fill(0, $n, -1);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i - 1; $j >= 0; $j--) {
                if ($candidates[$j]['end'] <= $candidates[$i]['start']) {
                    $prev[$i] = $j;
                    break;
                }
            }
        }

        $dp = array_fill(0, $n, 0);
        $take = array_fill(0, $n, false);
        $dp[0] = $candidates[0]['weight'];
        $take[0] = true;

        for ($i = 1; $i < $n; $i++) {
            $with = $candidates[$i]['weight'];
            if ($prev[$i] !== -1) {
                $with += $dp[$prev[$i]];
            }

            $without = $dp[$i - 1];
            if ($with > $without) {
                $dp[$i] = $with;
                $take[$i] = true;
            } else {
                $dp[$i] = $without;
                $take[$i] = false;
            }
        }

        $chosen = [];
        $i = $n - 1;
        while ($i >= 0) {
            if ($take[$i]) {
                $chosen[] = $candidates[$i]['index'];
                $i = $prev[$i];
            } else {
                $i--;
            }
        }

        return array_reverse($chosen);
    }

    private static function calcSplit(array $entry): int
    {
        $startMS = $entry['y'][0];
        $endMS = $entry['y'][1];
        $span = $endMS - $startMS;
        $total = $entry['paid'] + $entry['unpaid'];
        $percent = $total == 0 ? 0 : ($entry['paid'] / $total);
        $spanPaidPercent = $span * $percent;

        return $startMS + $spanPaidPercent;
    }

    protected static function splitPaidUnpaid(array $data): array
    {
        $dataCopy = $data;

        return [
            array_map(function ($entry) {
                $entry['y'][1] = self::calcSplit($entry);

                if ($entry['y'][1] === $entry['y'][0]) { // removes entry when should be invisible
                    return [];
                }

                return $entry;
            }, $data),
            array_map(function ($entry) {
                $entry['y'][0] = self::calcSplit($entry);

                if ($entry['y'][1] === $entry['y'][0]) { // removes entry when should be invisible
                    return [];
                }

                $entry['y'][0] += 10000000; // avoids visual collisions, 166.667 minutes

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
        [$paid, $unpaid] = self::splitPaidUnpaid(self::setX([
            ...self::formatForDataArray(Activity::all()->filter(fn ($act) => ! $act->archived)),
            //            ...self::formatCardsForDataArray(Card::all())
        ]));

        $todayColor = '#FFFFFF';

        return [
            'annotations' => [
                'xaxis' => [
                    [
                        'x' => now()->valueOf(),
                        'borderColor' => $todayColor,
                        'label' => [
                            'orientation' => 'horizontal',
                            'borderColor' => $todayColor,
                            'text' => 'Today',
                            'style' => [
                                'color' => '#000',
                                'background' => $todayColor,
                            ],
                        ],
                    ],
                ],
            ],

            'datalabels' => [
                'enabled' => false,
                'style' => [
                    'colors' => 'black',
                ],
            ],
            'chart' => [
                'zoom' => [
                    'allowMouseWheelZoom' => false,
                ],
                'type' => 'rangeBar',
                'height' => 250,
                //                'stacked' => true,
            ],
            'tooltip' => [
                'style' => [
                    'fontFamily' => 'inherit',
                ],
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
                //                [
                //                    'name' => 'Cards',
                //                    'data' => $cards,
                //                ],
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
            chart: {
                events: {
                      dataPointSelection: function (event, chartContext, config) {
                        let dpIndex = config.dataPointIndex;
                        let sIndex = config.seriesIndex;
                        let clickedEl = config.w.globals.initialSeries[sIndex].data[dpIndex];
                        window.open(clickedEl.link,'_self');
                      }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opt) {
                    let index = opt.dataPointIndex
                    let data = opt.w.globals.initialSeries[0].data[index]
                    return data.name;
                },
            },
            tooltip: {
                style: {
                    fontSize: '12px'
                },
                onDatasetHover: {
                    highlightDataSeries: false
                },
                custom: function({ series, seriesIndex, dataPointIndex, w }) {
                    var data = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
                    let name = data.name
                    let paid = data.paid.toFixed(2);
                    let unpaid = data.unpaid.toFixed(2);
                    let totalSpend = data.paid + data.unpaid
                    let paidPercent = (totalSpend === 0 ? 0 : (paid / totalSpend * 100)).toFixed(2);
                    let unpaidPercent = (totalSpend === 0 ? 0 : (unpaid / totalSpend * 100)).toFixed(2);

                    return `<div>
                        <span><span style='color: white'>${name}</span></span>
                        <br>
                        <span>
                            <span style='color: #32cd32;'>$${paid}</span> /
                            <span style='color: red'>$${unpaid}</span>
                        </span>
                        <br>
                        <span>
                            <span style='color: #32cd32;'>${paidPercent}%</span> /
                            <span style='color: red'>${unpaidPercent}%</span>
                        </span>
                    </div>`;
                }
            }

        }
        JS);
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
