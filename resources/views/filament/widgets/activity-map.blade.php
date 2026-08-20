@php
    $mapId = 'activity-map-'.uniqid();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Travel map
        </x-slot>

        <x-slot name="description">
            Activities with saved coordinates, connected chronologically. Nested vacations that end before their base also draw an automatic return line. Route color is the destination arrival method.
        </x-slot>

        <div
            wire:ignore
            class="space-y-3"
            x-data="activityTravelMap(@js([
                'points' => $points,
                'routes' => $routes,
                'mapId' => $mapId,
            ]))"
            x-init="init()"
        >
            @if (count($points) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No activities have locations yet. Edit an activity and search for a city to plot it here.
                </p>
            @else
                <div
                    id="{{ $mapId }}"
                    class="h-[28rem] w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700"
                ></div>

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                    @foreach ($legend as $item)
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="inline-block h-1 w-6 rounded"
                                style="background-color: {{ $item['color'] }}"
                            ></span>
                            {{ $item['label'] }}
                        </span>
                    @endforeach
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Map data ©
                    <a
                        href="https://www.openstreetmap.org/copyright"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="underline"
                    >OpenStreetMap</a>
                    contributors. Geocoding by Nominatim.
                </p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
