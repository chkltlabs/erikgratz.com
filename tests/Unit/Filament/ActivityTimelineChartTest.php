<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\ActivityResource\Widgets\ActivityTimelineChart;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ActivityTimelineChartTest extends TestCase
{
    #[Test]
    public function longer_gap_filler_gets_lower_row_than_shorter_overlapping_competitor(): void
    {
        $result = $this->setX([
            $this->bar('bookend_left', 0, 100),
            $this->bar('bookend_right', 200, 300),
            $this->bar('short_in_gap', 110, 140),
            $this->bar('long_in_gap', 105, 190),
        ]);

        $this->assertSame('0', $result[0]['x']);
        $this->assertSame('0', $result[1]['x']);
        $this->assertSame('0', $result[3]['x']);
        $this->assertSame('1', $result[2]['x']);
        $this->assertRowsHaveNoOverlaps($result);
    }

    #[Test]
    public function non_overlapping_bars_share_the_same_row(): void
    {
        $result = $this->setX([
            $this->bar('a', 0, 50),
            $this->bar('b', 50, 100),
            $this->bar('c', 100, 150),
        ]);

        $this->assertSame(['0', '0', '0'], array_column($result, 'x'));
        $this->assertRowsHaveNoOverlaps($result);
    }

    #[Test]
    public function equal_duration_bars_prefer_earlier_start_for_lower_row(): void
    {
        $result = $this->setX([
            $this->bar('later', 50, 150),
            $this->bar('earlier', 0, 100),
        ]);

        $this->assertSame('0', $result[1]['x']);
        $this->assertSame('1', $result[0]['x']);
        $this->assertRowsHaveNoOverlaps($result);
    }

    #[Test]
    public function identical_ranges_land_on_separate_rows(): void
    {
        $result = $this->setX([
            $this->bar('first', 0, 100),
            $this->bar('second', 0, 100),
        ]);

        $this->assertNotSame($result[0]['x'], $result[1]['x']);
        $this->assertContains($result[0]['x'], ['0', '1']);
        $this->assertContains($result[1]['x'], ['0', '1']);
        $this->assertRowsHaveNoOverlaps($result);
    }

    #[Test]
    public function zero_length_bars_are_still_placed(): void
    {
        $result = $this->setX([
            $this->bar('point', 50, 50),
            $this->bar('span', 0, 100),
        ]);

        $this->assertSame('0', $result[1]['x']);
        $this->assertNotNull($result[0]['x']);
        $this->assertRowsHaveNoOverlaps($result);
    }

    #[Test]
    public function bars_beyond_max_rows_keep_null_x(): void
    {
        $maxRows = (new ReflectionClass(ActivityTimelineChart::class))
            ->getConstant('MAX_ROWS');

        $data = [];
        for ($i = 0; $i < $maxRows + 1; $i++) {
            $data[] = $this->bar("overlap_$i", 0, 100);
        }

        $result = $this->setX($data);

        $rows = array_column($result, 'x');
        $this->assertCount(1, array_filter($rows, fn ($x) => $x === null));
        $this->assertCount($maxRows, array_unique(array_filter($rows, fn ($x) => $x !== null)));
        $this->assertRowsHaveNoOverlaps($result);
    }

    #[Test]
    public function set_x_preserves_input_order_and_only_assigns_x(): void
    {
        $input = [
            $this->bar('a', 0, 10),
            $this->bar('b', 20, 40),
            $this->bar('c', 5, 25),
        ];

        $result = $this->setX($input);

        $this->assertSame(['a', 'b', 'c'], array_column($result, 'name'));
        $this->assertSame($input[0]['y'], $result[0]['y']);
        $this->assertSame($input[1]['y'], $result[1]['y']);
        $this->assertSame($input[2]['y'], $result[2]['y']);
    }

    #[Test]
    public function split_paid_unpaid_splits_range_by_paid_ratio(): void
    {
        [$paid, $unpaid] = $this->splitPaidUnpaid([
            [
                'x' => '0',
                'y' => [0, 100],
                'name' => 'half',
                'paid' => 50.0,
                'unpaid' => 50.0,
            ],
        ]);

        $this->assertSame(50, $paid[0]['y'][1]);
        $this->assertSame(0, $paid[0]['y'][0]);
        $this->assertTrue($paid[0]['showLabel']);
        $this->assertSame(10000050, $unpaid[0]['y'][0]);
        $this->assertSame(100, $unpaid[0]['y'][1]);
        $this->assertFalse($unpaid[0]['showLabel']);
    }

    #[Test]
    public function split_paid_unpaid_drops_invisible_segments_when_fully_paid_or_unpaid(): void
    {
        [$paidFullyPaid, $unpaidFullyPaid] = $this->splitPaidUnpaid([
            [
                'x' => '0',
                'y' => [0, 100],
                'name' => 'all_paid',
                'paid' => 100.0,
                'unpaid' => 0.0,
            ],
        ]);

        $this->assertSame(100, $paidFullyPaid[0]['y'][1]);
        $this->assertTrue($paidFullyPaid[0]['showLabel']);
        $this->assertSame([], $unpaidFullyPaid);
        $this->assertTrue(array_is_list($unpaidFullyPaid));

        [$paidFullyUnpaid, $unpaidFullyUnpaid] = $this->splitPaidUnpaid([
            [
                'x' => '0',
                'y' => [0, 100],
                'name' => 'all_unpaid',
                'paid' => 0.0,
                'unpaid' => 100.0,
            ],
        ]);

        $this->assertSame([], $paidFullyUnpaid);
        $this->assertTrue(array_is_list($paidFullyUnpaid));
        $this->assertNotSame([], $unpaidFullyUnpaid[0]);
        $this->assertTrue($unpaidFullyUnpaid[0]['showLabel']);
    }

    #[Test]
    public function split_paid_unpaid_puts_name_on_the_larger_unpaid_half(): void
    {
        [$paid, $unpaid] = $this->splitPaidUnpaid([
            [
                'x' => '0',
                'y' => [0, 100],
                'name' => 'mostly_unpaid',
                'paid' => 25.0,
                'unpaid' => 75.0,
            ],
        ]);

        $this->assertFalse($paid[0]['showLabel']);
        $this->assertTrue($unpaid[0]['showLabel']);
    }

    #[Test]
    public function split_paid_unpaid_handles_zero_total(): void
    {
        [$paid, $unpaid] = $this->splitPaidUnpaid([
            [
                'x' => '0',
                'y' => [10, 110],
                'name' => 'empty',
                'paid' => 0.0,
                'unpaid' => 0.0,
            ],
        ]);

        $this->assertSame([], $paid);
        $this->assertTrue(array_is_list($paid));
        $this->assertNotSame([], $unpaid[0]);
        $this->assertSame(10000010, $unpaid[0]['y'][0]);
    }

    #[Test]
    public function split_paid_unpaid_reindexes_after_dropping_invisible_segments(): void
    {
        [$paid, $unpaid] = $this->splitPaidUnpaid([
            [
                'x' => '0',
                'y' => [0, 100],
                'name' => 'all_unpaid',
                'paid' => 0.0,
                'unpaid' => 100.0,
            ],
            [
                'x' => '1',
                'y' => [0, 100],
                'name' => 'all_paid',
                'paid' => 100.0,
                'unpaid' => 0.0,
            ],
        ]);

        $this->assertTrue(array_is_list($paid));
        $this->assertTrue(array_is_list($unpaid));
        $this->assertSame(['all_paid'], array_column($paid, 'name'));
        $this->assertSame(['all_unpaid'], array_column($unpaid, 'name'));
        $this->assertSame([0], array_keys($paid));
        $this->assertSame([0], array_keys($unpaid));
    }

    #[Test]
    public function split_card_sub_progress_splits_at_completion_and_darkens_remaining(): void
    {
        [$completed, $remaining] = $this->splitCardSubProgress([
            [
                'x' => 'C1',
                'y' => [0, 100],
                'name' => 'C1',
                'paid' => 50.0,
                'unpaid' => 50.0,
                'fillColor' => '#126bc5',
                'unpaidFillColor' => ActivityTimelineChart::darkenHex('#126bc5'),
            ],
        ]);

        $this->assertSame(0, $completed[0]['y'][0]);
        $this->assertSame(50, $completed[0]['y'][1]);
        $this->assertSame('#126bc5', $completed[0]['fillColor']);
        $this->assertTrue($completed[0]['showLabel']);
        $this->assertSame(50, $remaining[0]['y'][0]);
        $this->assertSame(100, $remaining[0]['y'][1]);
        $this->assertSame(ActivityTimelineChart::darkenHex('#126bc5'), $remaining[0]['fillColor']);
        $this->assertFalse($remaining[0]['showLabel']);
    }

    #[Test]
    public function split_card_sub_progress_omits_completed_segment_when_sub_is_untouched(): void
    {
        [$completed, $remaining] = $this->splitCardSubProgress([
            [
                'x' => 'C1',
                'y' => [0, 100],
                'name' => 'C1',
                'paid' => 0.0,
                'unpaid' => 4000.0,
                'fillColor' => '#ff9900',
                'unpaidFillColor' => ActivityTimelineChart::darkenHex('#ff9900'),
            ],
        ]);

        $this->assertSame([], $completed);
        $this->assertCount(1, $remaining);
        $this->assertTrue($remaining[0]['showLabel']);
        $this->assertSame(ActivityTimelineChart::darkenHex('#ff9900'), $remaining[0]['fillColor']);
    }

    #[Test]
    public function darken_hex_keeps_hue_and_falls_back_for_invalid_colors(): void
    {
        $this->assertSame('#0a3b6c', ActivityTimelineChart::darkenHex('#126bc5'));
        $this->assertSame('#374151', ActivityTimelineChart::darkenHex('not-a-color'));
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @return list<array<string, mixed>>
     */
    private function setX(array $data): array
    {
        $method = new ReflectionMethod(ActivityTimelineChart::class, 'setX');

        return $method->invoke(null, $data);
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function splitPaidUnpaid(array $data): array
    {
        $method = new ReflectionMethod(ActivityTimelineChart::class, 'splitPaidUnpaid');

        return $method->invoke(null, $data);
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function splitCardSubProgress(array $data): array
    {
        $method = new ReflectionMethod(ActivityTimelineChart::class, 'splitCardSubProgress');

        return $method->invoke(null, $data);
    }

    /**
     * @return array{x: null, y: array{0: int, 1: int}, name: string, lo: int, hi: int}
     */
    private function bar(string $name, int $start, int $end): array
    {
        return [
            'x' => null,
            'y' => [$start, $end],
            'name' => $name,
            'lo' => $start,
            'hi' => $end,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $bars
     */
    private function assertRowsHaveNoOverlaps(array $bars): void
    {
        $byRow = [];
        foreach ($bars as $bar) {
            if ($bar['x'] === null) {
                continue;
            }

            $byRow[$bar['x']][] = $bar;
        }

        foreach ($byRow as $row => $rowBars) {
            usort($rowBars, fn (array $a, array $b): int => $a['y'][0] <=> $b['y'][0]);

            for ($i = 1, $count = count($rowBars); $i < $count; $i++) {
                $this->assertLessThanOrEqual(
                    $rowBars[$i]['y'][0],
                    $rowBars[$i - 1]['y'][1],
                    "Row {$row} has overlapping bars {$rowBars[$i - 1]['name']} and {$rowBars[$i]['name']}",
                );
            }
        }
    }
}
