<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class ActivityMap extends Widget
{
    protected static bool $isLazy = true;

    protected static ?string $heading = 'Travel map';

    protected string $view = 'filament.widgets.activity-map';

    protected int|string|array $columnSpan = 'full';

    protected ?string $placeholderHeight = '28rem';

    /**
     * @return array{points: list<array<string, mixed>>, routes: list<array<string, mixed>>, legend: list<array{label: string, color: string, value: string}>}
     */
    protected function getViewData(): array
    {
        $activities = Activity::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get(['id', 'name', 'location_name', 'latitude', 'longitude', 'start_date', 'end_date', 'travel_method']);

        return [
            'points' => $this->formatPoints($activities),
            'routes' => $this->buildRoutes($activities),
            'legend' => $this->legend(),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return list<array<string, mixed>>
     */
    public static function formatPoints(Collection $activities): array
    {
        return $activities->map(fn (Activity $activity): array => [
            'id' => $activity->id,
            'name' => $activity->name,
            'location' => $activity->location_name,
            'lat' => (float) $activity->latitude,
            'lng' => (float) $activity->longitude,
            'start' => optional($activity->start_date)->toDateString(),
            'end' => optional($activity->end_date)->toDateString(),
            'travel_method' => $activity->travel_method instanceof TravelMethod
                ? $activity->travel_method->value
                : $activity->travel_method,
            'url' => $activity->getKey()
                ? ActivityResource::getUrl('edit', ['record' => $activity])
                : null,
        ])->values()->all();
    }

    /**
     * Chronological legs (colored by destination arrival method), plus automatic
     * return-to-base when a nested vacation finishes before its enclosing base.
     *
     * @param  Collection<int, Activity>  $activities
     * @return list<array{from: array{lat: float, lng: float}, to: array{lat: float, lng: float}, color: string, method: string|null, is_return: bool}>
     */
    public static function buildRoutes(Collection $activities): array
    {
        $items = $activities->values();
        $routes = [];
        $count = $items->count();

        for ($i = 1; $i < $count; $i++) {
            /** @var Activity $previous */
            $previous = $items[$i - 1];
            /** @var Activity $current */
            $current = $items[$i];

            $routes[] = self::makeRoute(
                from: $previous,
                to: $current,
                method: self::resolveMethod($current),
                isReturn: false,
            );
        }

        for ($i = 1; $i < $count; $i++) {
            $baseIndex = self::findEnclosingBaseIndex($items, $i);
            if ($baseIndex === null) {
                continue;
            }

            if (! self::shouldDrawReturnToBase($items, $i, $baseIndex)) {
                continue;
            }

            /** @var Activity $vacation */
            $vacation = $items[$i];
            /** @var Activity $base */
            $base = $items[$baseIndex];

            $routes[] = self::makeRoute(
                from: $vacation,
                to: $base,
                method: self::resolveMethod($vacation),
                isReturn: true,
            );
        }

        return $routes;
    }

    /**
     * Nearest prior activity whose end date is still after this vacation's end.
     *
     * @param  Collection<int, Activity>  $items
     */
    public static function findEnclosingBaseIndex(Collection $items, int $vacationIndex): ?int
    {
        /** @var Activity $vacation */
        $vacation = $items[$vacationIndex];
        $vacationEnd = Carbon::parse($vacation->end_date)->startOfDay();

        for ($i = $vacationIndex - 1; $i >= 0; $i--) {
            /** @var Activity $candidate */
            $candidate = $items[$i];
            $candidateEnd = Carbon::parse($candidate->end_date)->startOfDay();

            if ($vacationEnd->lt($candidateEnd)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Activity>  $items
     */
    public static function shouldDrawReturnToBase(
        Collection $items,
        int $vacationIndex,
        ?int $baseIndex = null,
    ): bool {
        $baseIndex ??= self::findEnclosingBaseIndex($items, $vacationIndex);
        if ($baseIndex === null) {
            return false;
        }

        /** @var Activity $base */
        $base = $items[$baseIndex];
        /** @var Activity $vacation */
        $vacation = $items[$vacationIndex];

        $baseEnd = Carbon::parse($base->end_date)->startOfDay();
        $vacationEnd = Carbon::parse($vacation->end_date)->startOfDay();

        if (! $vacationEnd->lt($baseEnd)) {
            return false;
        }

        $next = $items->get($vacationIndex + 1);
        if ($next !== null) {
            $nextEnd = Carbon::parse($next->end_date)->startOfDay();
            if ($nextEnd->lt($baseEnd)) {
                return false;
            }
        }

        $vacationStart = Carbon::parse($vacation->start_date)->toDateString();

        $sameDayNested = $items
            ->filter(function (Activity $other, int $index) use ($baseIndex, $vacationStart, $baseEnd): bool {
                if ($index <= $baseIndex) {
                    return false;
                }

                if (Carbon::parse($other->start_date)->toDateString() !== $vacationStart) {
                    return false;
                }

                return Carbon::parse($other->end_date)->startOfDay()->lt($baseEnd)
                    && Carbon::parse($other->start_date)->startOfDay()->lt($baseEnd);
            })
            ->sortBy([
                fn (Activity $activity) => Carbon::parse($activity->start_date)->timestamp,
                fn (Activity $activity) => $activity->id ?? 0,
            ])
            ->values();

        if ($sameDayNested->count() <= 1) {
            return true;
        }

        $last = $sameDayNested->last();

        return ($last->id ?? null) === ($vacation->id ?? null)
            || $last === $vacation;
    }

    /**
     * @return array{from: array{lat: float, lng: float}, to: array{lat: float, lng: float}, color: string, method: string|null, is_return: bool}
     */
    protected static function makeRoute(Activity $from, Activity $to, ?TravelMethod $method, bool $isReturn): array
    {
        return [
            'from' => [
                'lat' => (float) $from->latitude,
                'lng' => (float) $from->longitude,
            ],
            'to' => [
                'lat' => (float) $to->latitude,
                'lng' => (float) $to->longitude,
            ],
            'color' => $method?->color() ?? '#6b7280',
            'method' => $method?->value,
            'is_return' => $isReturn,
        ];
    }

    protected static function resolveMethod(Activity $activity): ?TravelMethod
    {
        if ($activity->travel_method instanceof TravelMethod) {
            return $activity->travel_method;
        }

        if (TravelMethod::hasValue($activity->travel_method)) {
            return TravelMethod::fromValue($activity->travel_method);
        }

        return null;
    }

    /**
     * @return list<array{label: string, color: string, value: string}>
     */
    public static function legend(): array
    {
        return collect(TravelMethod::getInstances())
            ->map(fn (TravelMethod $method): array => [
                'value' => $method->value,
                'label' => $method->description,
                'color' => $method->color(),
            ])
            ->values()
            ->all();
    }
}
