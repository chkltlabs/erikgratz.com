<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\BenefitUsage;
use App\Models\Card;
use App\Models\PointRedemption;
use App\Models\StateDump;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DumpChartData
{
    public const PAST_STATS_CACHE = 'stateDumps';

    public const BENEFIT_USAGE_CACHE = 'stateDumpBenefitUsageCharts';

    public const REDEMPTIONS_CACHE = 'stateDumpRedemptionCharts';

    public static function forgetCaches(): void
    {
        Cache::forget(self::PAST_STATS_CACHE);
        Cache::forget(self::BENEFIT_USAGE_CACHE);
        Cache::forget(self::REDEMPTIONS_CACHE);
    }

    /**
     * @return array{
     *     total: array<int, float>,
     *     cards: list<array{id: int, name: string, color: string, values: array<int, float>}>
     * }
     */
    public static function benefitUsageVsFees(): array
    {
        return Cache::remember(self::BENEFIT_USAGE_CACHE, now()->endOfDay(), function (): array {
            $dumps = self::dumpsInWindow();
            $cards = Card::query()->get(['id', 'name', 'color']);

            $total = [];
            $perCard = $cards->mapWithKeys(fn (Card $card): array => [
                $card->id => [],
            ])->all();

            foreach ($dumps as $dump) {
                $timestamp = $dump->created_at->timestamp;
                $data = $dump->data ?? [];
                $cardRows = self::indexRows($data[Card::class] ?? []);
                $capturedByCard = self::capturedUsageByCard($data[BenefitUsage::class] ?? []);

                $dumpTotal = 0.0;
                foreach ($cards as $card) {
                    $row = $cardRows[$card->id] ?? null;
                    if ($row === null) {
                        $perCard[$card->id][$timestamp] = 0.0;

                        continue;
                    }

                    $captured = $capturedByCard[$card->id] ?? 0.0;
                    $fees = self::annualFeesThrough(
                        $row['date_opened'] ?? null,
                        (float) ($row['annual_fee'] ?? 0),
                        $dump->created_at,
                    );
                    $net = $captured - $fees;
                    $perCard[$card->id][$timestamp] = $net;
                    $dumpTotal += $net;
                }

                $total[$timestamp] = $dumpTotal;
            }

            return [
                'total' => $total,
                'cards' => $cards
                    ->map(fn (Card $card): array => [
                        'id' => $card->id,
                        'name' => $card->name,
                        'color' => $card->color ?: '#6b7280',
                        'values' => $perCard[$card->id] ?? [],
                    ])
                    ->values()
                    ->all(),
            ];
        });
    }

    /**
     * @return array{
     *     cash_value: array<int, float>,
     *     money_spent: array<int, float>,
     *     points_spent: array<int, float>,
     *     money_saved: array<int, float>,
     *     cents_per_point: array<int, float>,
     *     breakdown: list<array{cash_value: float, money_spent: float, points_spent: float, money_saved: float, cents_per_point: float, label: string}>
     * }
     */
    public static function redemptionValue(): array
    {
        return Cache::remember(self::REDEMPTIONS_CACHE, now()->endOfDay(), function (): array {
            $cashValue = [];
            $moneySpent = [];
            $pointsSpent = [];
            $moneySaved = [];
            $centsPerPoint = [];
            $breakdown = [];

            foreach (self::dumpsInWindow() as $dump) {
                $timestamp = $dump->created_at->timestamp;
                $totals = self::redemptionTotals($dump->data[PointRedemption::class] ?? []);
                $saved = $totals['cash_value'] - $totals['money_spent'];
                $cpp = $totals['points_spent'] > 0
                    ? ($saved / $totals['points_spent']) * 100
                    : 0.0;

                $cashValue[$timestamp] = $totals['cash_value'];
                $moneySpent[$timestamp] = $totals['money_spent'];
                $pointsSpent[$timestamp] = $totals['points_spent'];
                $moneySaved[$timestamp] = $saved;
                $centsPerPoint[$timestamp] = $cpp;
                $breakdown[] = [
                    'cash_value' => $totals['cash_value'],
                    'money_spent' => $totals['money_spent'],
                    'points_spent' => $totals['points_spent'],
                    'money_saved' => $saved,
                    'cents_per_point' => $cpp,
                    'label' => self::centsPerPointLabel($totals['points_spent'], $saved, $cpp),
                ];
            }

            return [
                'cash_value' => $cashValue,
                'money_spent' => $moneySpent,
                'points_spent' => $pointsSpent,
                'money_saved' => $moneySaved,
                'cents_per_point' => $centsPerPoint,
                'breakdown' => $breakdown,
            ];
        });
    }

    /**
     * @param  array<int, float>  $series
     * @return list<array{x: Carbon, y: float}>
     */
    public static function toXy(array $series): array
    {
        return array_values(collect($series)->map(fn (float $value, int $timestamp): array => [
            'x' => Carbon::createFromTimestamp($timestamp),
            'y' => round($value, 2),
        ])->all());
    }

    /**
     * @param  array<int, float>  $low
     * @param  array<int, float>  $high
     * @return list<array{x: Carbon, y: array{0: float, 1: float}}>
     */
    public static function toRangeXy(array $low, array $high): array
    {
        $points = [];
        foreach ($low as $timestamp => $lowValue) {
            $highValue = $high[$timestamp] ?? $lowValue;
            $points[] = [
                'x' => Carbon::createFromTimestamp((int) $timestamp),
                'y' => [round((float) $lowValue, 2), round((float) $highValue, 2)],
            ];
        }

        return $points;
    }

    public static function annualFeesThrough(mixed $dateOpened, float $fee, Carbon $asOf): float
    {
        if ($fee <= 0 || ! filled($dateOpened)) {
            return 0.0;
        }

        $opened = Carbon::parse((string) $dateOpened)->startOfDay();
        $asOfDay = $asOf->copy()->startOfDay();
        if ($opened->gt($asOfDay)) {
            return 0.0;
        }

        $events = 0;
        for ($anniversary = $opened->copy(); $anniversary->lte($asOfDay); $anniversary->addYear()) {
            $events++;
        }

        return $events * $fee;
    }

    /**
     * @return Collection<int, StateDump>
     */
    protected static function dumpsInWindow(): Collection
    {
        return StateDump::query()
            ->where('created_at', '>=', now()->subMonths(6))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int|string, array<string, mixed>>
     */
    protected static function indexRows(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! array_key_exists('id', $row)) {
                continue;
            }
            $indexed[$row['id']] = $row;
        }

        return $indexed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, float>
     */
    protected static function capturedUsageByCard(array $rows): array
    {
        $captured = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cardId = $row['card_id'] ?? null;
            if ($cardId === null) {
                continue;
            }

            $captured[(int) $cardId] = ($captured[(int) $cardId] ?? 0.0)
                + (float) ($row['captured'] ?? $row['amount'] ?? 0);
        }

        return $captured;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{cash_value: float, money_spent: float, points_spent: float}
     */
    protected static function redemptionTotals(array $rows): array
    {
        $cashValue = 0.0;
        $moneySpent = 0.0;
        $pointsSpent = 0.0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cashValue += (float) ($row['cash_value'] ?? 0);
            $moneySpent += (float) ($row['money_spent'] ?? 0);
            $pointsSpent += (float) ($row['points_spent'] ?? 0);
        }

        return [
            'cash_value' => $cashValue,
            'money_spent' => $moneySpent,
            'points_spent' => $pointsSpent,
        ];
    }

    protected static function centsPerPointLabel(float $pointsSpent, float $moneySaved, float $centsPerPoint): string
    {
        return number_format($pointsSpent).' pts / $'.number_format($moneySaved, 2).' saved = '.number_format($centsPerPoint, 2).'¢/pt';
    }
}
