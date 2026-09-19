<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ActivityMap extends Widget
{
    use InteractsWithPageTable;

    protected static bool $isLazy = true;

    protected static ?string $heading = 'Travel map';

    protected string $view = 'filament.widgets.activity-map';

    protected int|string|array $columnSpan = 'full';

    protected ?string $placeholderHeight = '28rem';

    public const KIND_TRAVEL = 'travel';

    public const KIND_CONTINUE = 'continue';

    public const KIND_RETURN = 'return';

    protected function getTablePage(): string
    {
        return ListActivities::class;
    }

    /**
     * @return array{points: list<array<string, mixed>>, routes: list<array<string, mixed>>, legend: list<array{label: string, color: string, value: string}>, mapKey: string}
     */
    protected function getViewData(): array
    {
        $activities = $this->getMappedActivities();

        return [
            'points' => $this->formatPoints($activities),
            'routes' => $this->buildRoutes($activities),
            'legend' => $this->legend(),
            'mapKey' => $this->mapRevisionKey(),
        ];
    }

    /**
     * Activities for the map: same filters/search as ListActivities, plus coordinates.
     * Always ordered chronologically so travel legs stay correct regardless of table sort.
     *
     * @return Collection<int, Activity>
     */
    protected function getMappedActivities(): Collection
    {
        return $this->mappedActivitiesQuery()
            ->reorder('activities.start_date')
            ->orderBy('activities.id')
            ->get();
    }

    protected function mappedActivitiesQuery(): Builder
    {
        return $this->getPageTableQuery()
            ->whereNotNull('activities.latitude')
            ->whereNotNull('activities.longitude');
    }

    protected function mapRevisionKey(): string
    {
        return md5(json_encode([
            $this->tableFilters,
            $this->tableSearch,
            $this->tableColumnSearches,
            $this->tableSort,
            $this->activeTab,
        ], JSON_THROW_ON_ERROR));
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
     * Build map routes:
     * - travel: sequential non-nested hops (and base → first vacation stop)
     * - continue: vacation stop → next stop while still nested in the same base
     * - return: last nested vacation stop → enclosing base
     *
     * @param  Collection<int, Activity>  $activities
     * @return list<array{from: array{lat: float, lng: float}, to: array{lat: float, lng: float}, color: string, method: string|null, kind: string, is_return: bool, is_continue: bool}>
     */
    public static function buildRoutes(Collection $activities): array
    {
        $items = $activities->values();
        $count = $items->count();
        $routes = [];
        /** @var array<string, true> $drawnEdges */
        $drawnEdges = [];

        for ($baseIndex = 0; $baseIndex < $count; $baseIndex++) {
            $nestedIndices = self::contiguousNestedIndices($items, $baseIndex);
            if ($nestedIndices === []) {
                continue;
            }

            /** @var Activity $base */
            $base = $items[$baseIndex];
            $firstIndex = $nestedIndices[0];
            /** @var Activity $firstStop */
            $firstStop = $items[$firstIndex];

            $outboundKey = self::edgeKey($baseIndex, $firstIndex);
            $routes[] = self::makeRoute(
                from: $base,
                to: $firstStop,
                method: self::resolveMethod($firstStop),
                kind: self::KIND_TRAVEL,
            );
            $drawnEdges[$outboundKey] = true;

            for ($n = 1; $n < count($nestedIndices); $n++) {
                $fromIndex = $nestedIndices[$n - 1];
                $toIndex = $nestedIndices[$n];
                /** @var Activity $fromStop */
                $fromStop = $items[$fromIndex];
                /** @var Activity $toStop */
                $toStop = $items[$toIndex];

                $continueKey = self::edgeKey($fromIndex, $toIndex);
                $routes[] = self::makeRoute(
                    from: $fromStop,
                    to: $toStop,
                    method: self::resolveMethod($toStop),
                    kind: self::KIND_CONTINUE,
                );
                $drawnEdges[$continueKey] = true;
            }

            $lastIndex = $nestedIndices[array_key_last($nestedIndices)];
            if (self::shouldDrawReturnToBase($items, $lastIndex, $baseIndex)) {
                /** @var Activity $lastStop */
                $lastStop = $items[$lastIndex];
                $routes[] = self::makeRoute(
                    from: $lastStop,
                    to: $base,
                    method: self::resolveMethod($lastStop),
                    kind: self::KIND_RETURN,
                );
            }
        }

        for ($i = 1; $i < $count; $i++) {
            $key = self::edgeKey($i - 1, $i);
            if (isset($drawnEdges[$key])) {
                continue;
            }

            /** @var Activity $previous */
            $previous = $items[$i - 1];
            /** @var Activity $current */
            $current = $items[$i];

            $routes[] = self::makeRoute(
                from: $previous,
                to: $current,
                method: self::resolveMethod($current),
                kind: self::KIND_TRAVEL,
            );
        }

        return $routes;
    }

    /**
     * Contiguous activities after $baseIndex that end before the base ends.
     *
     * @param  Collection<int, Activity>  $items
     * @return list<int>
     */
    public static function contiguousNestedIndices(Collection $items, int $baseIndex): array
    {
        /** @var Activity $base */
        $base = $items[$baseIndex];
        $baseEnd = Carbon::parse($base->end_date)->startOfDay();
        $nested = [];

        for ($j = $baseIndex + 1; $j < $items->count(); $j++) {
            /** @var Activity $candidate */
            $candidate = $items[$j];
            $candidateEnd = Carbon::parse($candidate->end_date)->startOfDay();

            if (! $candidateEnd->lt($baseEnd)) {
                break;
            }

            $nested[] = $j;
        }

        return $nested;
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

        $nestedIndices = self::contiguousNestedIndices($items, $baseIndex);
        if ($nestedIndices === [] || $nestedIndices[array_key_last($nestedIndices)] !== $vacationIndex) {
            return false;
        }

        $vacationStart = Carbon::parse($vacation->start_date)->toDateString();

        $sameDayNested = collect($nestedIndices)
            ->map(fn (int $index): Activity => $items[$index])
            ->filter(fn (Activity $other): bool => Carbon::parse($other->start_date)->toDateString() === $vacationStart)
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

    protected static function edgeKey(int $fromIndex, int $toIndex): string
    {
        return $fromIndex.'>'.$toIndex;
    }

    /**
     * @return array{from: array{lat: float, lng: float}, to: array{lat: float, lng: float}, color: string, method: string|null, kind: string, is_return: bool, is_continue: bool}
     */
    protected static function makeRoute(Activity $from, Activity $to, ?TravelMethod $method, string $kind): array
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
            'kind' => $kind,
            'is_return' => $kind === self::KIND_RETURN,
            'is_continue' => $kind === self::KIND_CONTINUE,
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
