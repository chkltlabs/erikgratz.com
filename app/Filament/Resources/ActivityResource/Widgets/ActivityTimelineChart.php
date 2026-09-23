<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\CardResource;
use App\Models\Activity;
use App\Models\Card;
use Carbon\Carbon;
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

    public const DEFAULT_PAID_COLOR = '#32cd32';

    public const CARDS_AXIS_LABEL = 'cards';

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
        return $models->values()->map(function ($model) {
            $color = $model->color ?: '#6b7280';
            $requirement = (float) $model->points_bonus_spend;
            $progress = $model->subSpendProgress();
            $completed = $requirement > 0 ? min($progress, $requirement) : 0.0;
            $remaining = max(0.0, $requirement - $completed);

            return [
                'x' => self::CARDS_AXIS_LABEL,
                'y' => [
                    Carbon::parse($model->date_opened)->valueOf(),
                    Carbon::parse($model->date_opened)->modify($model->points_bonus_period ?? '+1 Day')->valueOf(),
                ],
                'name' => $model->name,
                'amount' => $model->points_bonus_spend,
                'class' => get_class($model),
                'lo' => Carbon::parse($model->date_opened)->valueOf(),
                'hi' => Carbon::parse($model->date_opened)->modify($model->points_bonus_period ?? '+1 Day')->valueOf(),
                'paid' => $completed,
                'unpaid' => $remaining,
                'total_spend' => $requirement,
                'fillColor' => $color,
                'unpaidFillColor' => self::darkenHex($color),
                'link' => CardResource::getUrl('index', [
                    'record' => $model,
                ]),
            ];
        })->all();
    }

    protected static function formatForDataArray(Collection $models): array
    {
        return $models->values()->map(function ($model) {
            $color = $model->color ?: self::DEFAULT_PAID_COLOR;

            return [
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
                'days' => (int) $model->total_days,
                'paid' => $model->paid,
                'unpaid' => $model->unpaid,
                'total_spend' => $model->total_spend,
                'fillColor' => $color,
                'unpaidFillColor' => self::darkenHex($color),
                'link' => ActivityResource::getUrl('edit', [
                    'record' => $model,
                ]),
            ];
        })->all();
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
        $paid = [];
        $unpaid = [];

        foreach ($data as $entry) {
            $split = self::calcSplit($entry);
            $start = $entry['y'][0];
            $end = $entry['y'][1];
            $color = $entry['fillColor'] ?? self::DEFAULT_PAID_COLOR;
            $dark = $entry['unpaidFillColor'] ?? self::darkenHex((string) $color);
            $paidExists = $split > $start;
            $unpaidExists = $split < $end;
            $labelOnPaid = $paidExists && (! $unpaidExists || self::paidWinsLabel($entry));

            if ($paidExists) {
                $done = $entry;
                $done['y'] = [$start, $split];
                $done['fillColor'] = $color;
                $done['showLabel'] = $labelOnPaid;
                $paid[] = $done;
            }

            if ($unpaidExists) {
                $rest = $entry;
                $rest['y'] = [$split + 10000000, $end];
                $rest['fillColor'] = $dark;
                $rest['showLabel'] = ! $labelOnPaid;
                $unpaid[] = $rest;
            }
        }

        return [
            self::asApexSeriesData($paid),
            self::asApexSeriesData($unpaid),
        ];
    }

    /**
     * Split a card SUB bar at completion percentage. Remaining uses a darker shade.
     * Adjacent segments share the split timestamp so they read as one bar.
     *
     * @param  list<array<string, mixed>>  $data
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    protected static function splitCardSubProgress(array $data): array
    {
        $completed = [];
        $remaining = [];

        foreach ($data as $entry) {
            $split = self::calcSplit($entry);
            $start = $entry['y'][0];
            $end = $entry['y'][1];
            $color = $entry['fillColor'] ?? '#6b7280';
            $dark = $entry['unpaidFillColor'] ?? self::darkenHex((string) $color);
            $paidExists = $split > $start;
            $unpaidExists = $split < $end;
            $labelOnPaid = $paidExists && (! $unpaidExists || self::paidWinsLabel($entry));

            if ($paidExists) {
                $done = $entry;
                $done['y'] = [$start, $split];
                $done['fillColor'] = $color;
                $done['showLabel'] = $labelOnPaid;
                $completed[] = $done;
            }

            if ($unpaidExists) {
                $rest = $entry;
                $rest['y'] = [$split, $end];
                $rest['fillColor'] = $dark;
                $rest['showLabel'] = ! $labelOnPaid;
                $remaining[] = $rest;
            }
        }

        return [
            self::asApexSeriesData($completed),
            self::asApexSeriesData($remaining),
        ];
    }

    public static function darkenHex(string $hex, float $factor = 0.55): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#374151';
        }

        $red = (int) round(hexdec(substr($hex, 0, 2)) * $factor);
        $green = (int) round(hexdec(substr($hex, 2, 2)) * $factor);
        $blue = (int) round(hexdec(substr($hex, 4, 2)) * $factor);

        return sprintf('#%02x%02x%02x', min(255, $red), min(255, $green), min(255, $blue));
    }

    /**
     * Paid half gets the name on a tie so only one label is drawn.
     *
     * @param  array<string, mixed>  $entry
     */
    private static function paidWinsLabel(array $entry): bool
    {
        return (float) ($entry['paid'] ?? 0) >= (float) ($entry['unpaid'] ?? 0);
    }

    /**
     * ApexCharts calls `series[i].data.filter`. PHP arrays with holes JSON-encode as
     * objects, which have no `.filter`. Empty `[]` placeholders are also invalid points.
     *
     * @param  array<int, array<string, mixed>|list<empty>>  $points
     * @return list<array<string, mixed>>
     */
    private static function asApexSeriesData(array $points): array
    {
        return array_values(array_filter(
            $points,
            fn (mixed $point): bool => is_array($point)
                && $point !== []
                && array_key_exists('x', $point)
                && isset($point['y'][0], $point['y'][1]),
        ));
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        [$paid, $unpaid] = self::splitPaidUnpaid(self::setX(
            self::formatForDataArray(
                Activity::all()->filter(fn ($act) => ! $act->archived)->values(),
            ),
        ));
        $openCards = $this->openSubCards();
        [$cardCompleted, $cardRemaining] = self::splitCardSubProgress(
            self::formatCardsForDataArray($openCards),
        );
        $cards = self::asApexSeriesData([...$cardCompleted, ...$cardRemaining]);

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
                'height' => 250 + ($openCards->count() * 32),
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
                [
                    'name' => 'Cards',
                    'data' => $cards,
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
                    'show' => true,
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => [
                self::DEFAULT_PAID_COLOR,
                self::darkenHex(self::DEFAULT_PAID_COLOR),
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
                    let data = opt.w.globals.initialSeries[opt.seriesIndex].data[opt.dataPointIndex];
                    return data && data.showLabel && data.name ? data.name : '';
                },
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return /^\d+$/.test(String(val)) ? '' : val;
                    }
                }
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
                    let daysHtml = data.days
                        ? `<br><span>${data.days} day${data.days === 1 ? '' : 's'}</span>`
                        : '';

                    return `<div>
                        <span><span style='color: white'>${name}</span></span>
                        ${daysHtml}
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

    /**
     * @return Collection<int, Card>
     */
    private function openSubCards(): Collection
    {
        return Card::query()
            ->with('planned_payments')
            ->get()
            ->reject(fn (Card $card): bool => $card->has_satisfied_sub)
            ->values();
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
