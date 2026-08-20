<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Enums\TravelMethod;
use App\Services\Geocoding\NominatimGeocoder;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

class LocationPicker
{
    /**
     * Filament Section wrapping arrival method + city search fields.
     */
    public static function section(): Section
    {
        return Section::make('Location')
            ->description('Where this activity took place and how you arrived.')
            ->icon('heroicon-o-map-pin')
            ->columns(1)
            ->schema(self::fields());
    }

    /**
     * @return array<int, Hidden|Select|TextInput>
     */
    public static function fields(): array
    {
        return [
            Select::make('travel_method')
                ->label('Arrival method')
                ->helperText('How you traveled to this activity. Colors the incoming (and return) route on the map.')
                ->options(TravelMethod::asSelectArray())
                ->native(false)
                ->nullable(),
            TextInput::make('location_search')
                ->label('Search city')
                ->placeholder('e.g. Istanbul, Turkey')
                ->dehydrated(false)
                ->helperText('Type a city name, then press Search. Results come from OpenStreetMap Nominatim.')
                ->afterStateHydrated(function (TextInput $component, Get $get, mixed $state): void {
                    if (filled($state) || filled($get('location_name'))) {
                        return;
                    }

                    $title = $get('name');
                    if (filled($title)) {
                        $component->state($title);
                    }
                })
                ->suffixAction(
                    Action::make('searchLocation')
                        ->icon('heroicon-m-magnifying-glass')
                        ->label('Search')
                        ->action(fn (Get $get, Set $set) => self::search($get, $set))
                ),
            Select::make('selected_location')
                ->label('Select location')
                ->dehydrated(false)
                ->native(false)
                ->visible(fn (Get $get): bool => filled($get('location_search_results')))
                ->options(fn (Get $get): array => self::resultOptions($get))
                ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => self::applySelection($state, $get, $set)),
            TextInput::make('location_name')
                ->label('Saved location')
                ->disabled()
                ->dehydrated()
                ->placeholder('No location selected')
                ->suffixAction(
                    Action::make('clearLocation')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->visible(fn (Get $get): bool => filled($get('location_name')))
                        ->action(fn (Set $set) => self::clear($set))
                ),
            Hidden::make('latitude'),
            Hidden::make('longitude'),
            Hidden::make('location_search_results')
                ->dehydrated(false)
                ->default([]),
        ];
    }

    /**
     * @return array<int, Section>
     *
     * @deprecated Use section() in form schemas instead.
     */
    public static function make(): array
    {
        return [self::section()];
    }

    public static function search(Get $get, Set $set): void
    {
        $query = trim((string) $get('location_search'));

        if ($query === '') {
            Notification::make()
                ->title('Enter a city to search')
                ->warning()
                ->send();

            return;
        }

        try {
            $results = app(NominatimGeocoder::class)->search($query);
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Location search failed')
                ->body('Please try again in a moment.')
                ->danger()
                ->send();

            return;
        }

        if ($results === []) {
            $set('location_search_results', []);
            Notification::make()
                ->title('No locations found')
                ->warning()
                ->send();

            return;
        }

        $set('location_search_results', $results);
        $set('selected_location', null);

        Notification::make()
            ->title(count($results).' location(s) found')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public static function resultOptions(Get $get): array
    {
        /** @var array<int, array{place_id: string|int, display_name: string}> $results */
        $results = $get('location_search_results') ?? [];

        return collect($results)
            ->mapWithKeys(fn (array $result): array => [
                (string) $result['place_id'] => $result['display_name'],
            ])
            ->all();
    }

    public static function applySelection(?string $state, Get $get, Set $set): void
    {
        if ($state === null) {
            return;
        }

        /** @var array<int, array{place_id: string|int, display_name: string, latitude: float, longitude: float}> $results */
        $results = $get('location_search_results') ?? [];
        $match = collect($results)->first(
            fn (array $result): bool => (string) $result['place_id'] === (string) $state
        );

        if ($match === null) {
            return;
        }

        $set('location_name', $match['display_name']);
        $set('latitude', $match['latitude']);
        $set('longitude', $match['longitude']);
    }

    public static function clear(Set $set): void
    {
        $set('location_name', null);
        $set('latitude', null);
        $set('longitude', null);
        $set('selected_location', null);
        $set('location_search_results', []);
        $set('location_search', null);
    }
}
