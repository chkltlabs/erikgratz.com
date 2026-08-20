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
