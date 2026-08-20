<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
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
            'url' => ActivityResource::getUrl('edit', ['record' => $activity]),
        ])->values()->all();
    }

    /**
     * Incoming route to each activity after the first, colored by the destination's travel_method.
     *
     * @param  Collection<int, Activity>  $activities
     * @return list<array{from: array{lat: float, lng: float}, to: array{lat: float, lng: float}, color: string, method: string|null}>
     */
    public static function buildRoutes(Collection $activities): array
    {
        $routes = [];
        $previous = null;

        foreach ($activities as $activity) {
            if ($previous !== null) {
                $method = $activity->travel_method instanceof TravelMethod
                    ? $activity->travel_method
                    : (TravelMethod::hasValue($activity->travel_method)
                        ? TravelMethod::fromValue($activity->travel_method)
                        : null);

                $routes[] = [
                    'from' => [
                        'lat' => (float) $previous->latitude,
                        'lng' => (float) $previous->longitude,
                    ],
                    'to' => [
                        'lat' => (float) $activity->latitude,
                        'lng' => (float) $activity->longitude,
                    ],
                    'color' => $method?->color() ?? '#6b7280',
                    'method' => $method?->value,
                ];
            }

            $previous = $activity;
        }

        return $routes;
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
