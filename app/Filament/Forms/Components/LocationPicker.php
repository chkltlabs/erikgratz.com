<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Services\Geocoding\NominatimGeocoder;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

class LocationPicker
{
    /**
     * @return array<int, Group|Hidden|Select|TextInput>
     */
    public static function make(): array
    {
        return [
            Group::make([
                TextInput::make('location_search')
                    ->label('Search city')
                    ->placeholder('e.g. Istanbul, Turkey')
                    ->dehydrated(false)
                    ->helperText('Type a city name, then press Search. Results come from OpenStreetMap Nominatim.')
                    ->suffixAction(
                        Action::make('searchLocation')
                            ->icon('heroicon-m-magnifying-glass')
                            ->label('Search')
                            ->action(function (Get $get, Set $set): void {
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
                            })
                    ),
                Select::make('selected_location')
                    ->label('Select location')
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => filled($get('location_search_results')))
                    ->options(function (Get $get): array {
                        /** @var array<int, array{place_id: string|int, display_name: string}> $results */
                        $results = $get('location_search_results') ?? [];

                        return collect($results)
                            ->mapWithKeys(fn (array $result): array => [
                                (string) $result['place_id'] => $result['display_name'],
                            ])
                            ->all();
                    })
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
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
                    }),
                TextInput::make('location_name')
                    ->label('Location')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('No location selected')
                    ->suffixAction(
                        Action::make('clearLocation')
                            ->icon('heroicon-m-x-mark')
                            ->color('danger')
                            ->visible(fn (Get $get): bool => filled($get('location_name')))
                            ->action(function (Set $set): void {
                                $set('location_name', null);
                                $set('latitude', null);
                                $set('longitude', null);
                                $set('selected_location', null);
                                $set('location_search_results', []);
                                $set('location_search', null);
                            })
                    ),
                Hidden::make('latitude'),
                Hidden::make('longitude'),
                Hidden::make('location_search_results')
                    ->dehydrated(false)
                    ->default([]),
            ])->columnSpanFull(),
        ];
    }
}
