<?php

namespace Tests\Unit\Filament;

use App\Filament\Forms\Components\LocationPicker;
use App\Services\Geocoding\NominatimGeocoder;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class LocationPickerTest extends TestCase
{
    #[Test]
    public function make_returns_location_section(): void
    {
        $components = LocationPicker::make();

        $this->assertNotEmpty($components);
        $this->assertSame('Location', $components[0]->getHeading());
    }

    #[Test]
    public function search_warns_when_query_is_empty(): void
    {
        $state = ['location_search' => '   '];
        LocationPicker::search($this->formGet($state), $this->formSet($state));

        Notification::assertNotified('Enter a city to search');
    }

    #[Test]
    public function search_stores_results_from_geocoder(): void
    {
        $this->mock(NominatimGeocoder::class, function ($mock) {
            $mock->shouldReceive('search')
                ->once()
                ->with('Berlin')
                ->andReturn([
                    [
                        'place_id' => '123',
                        'display_name' => 'Berlin, Germany',
                        'latitude' => 52.52,
                        'longitude' => 13.40,
                    ],
                ]);
        });

        $state = ['location_search' => 'Berlin'];
        LocationPicker::search($this->formGet($state), $this->formSet($state));

        $this->assertCount(1, $state['location_search_results']);
        $this->assertNull($state['selected_location']);
        Notification::assertNotified('1 location(s) found');
    }

    #[Test]
    public function search_handles_empty_geocoder_results(): void
    {
        $this->mock(NominatimGeocoder::class, function ($mock) {
            $mock->shouldReceive('search')->once()->andReturn([]);
        });

        $state = ['location_search' => 'Nowhere'];
        LocationPicker::search($this->formGet($state), $this->formSet($state));

        $this->assertSame([], $state['location_search_results']);
        Notification::assertNotified('No locations found');
    }

    #[Test]
    public function search_handles_geocoder_exceptions(): void
    {
        $this->mock(NominatimGeocoder::class, function ($mock) {
            $mock->shouldReceive('search')->once()->andThrow(new RuntimeException('boom'));
        });

        $state = ['location_search' => 'Berlin'];
        LocationPicker::search($this->formGet($state), $this->formSet($state));

        Notification::assertNotified('Location search failed');
    }

    #[Test]
    public function result_options_map_place_ids_to_labels(): void
    {
        $state = [
            'location_search_results' => [
                ['place_id' => 9, 'display_name' => 'Paris, France'],
            ],
        ];

        $this->assertSame(
            ['9' => 'Paris, France'],
            LocationPicker::resultOptions($this->formGet($state))
        );
    }

    #[Test]
    public function apply_selection_sets_location_fields(): void
    {
        $state = [
            'location_search_results' => [
                [
                    'place_id' => 'abc',
                    'display_name' => 'Lisbon, Portugal',
                    'latitude' => 38.7,
                    'longitude' => -9.1,
                ],
            ],
        ];

        LocationPicker::applySelection('abc', $this->formGet($state), $this->formSet($state));

        $this->assertSame('Lisbon, Portugal', $state['location_name']);
        $this->assertSame(38.7, $state['latitude']);
        $this->assertSame(-9.1, $state['longitude']);
    }

    #[Test]
    public function apply_selection_ignores_null_and_unknown_ids(): void
    {
        $state = [
            'location_name' => 'Keep',
            'location_search_results' => [
                [
                    'place_id' => 'abc',
                    'display_name' => 'Lisbon, Portugal',
                    'latitude' => 38.7,
                    'longitude' => -9.1,
                ],
            ],
        ];

        LocationPicker::applySelection(null, $this->formGet($state), $this->formSet($state));
        LocationPicker::applySelection('missing', $this->formGet($state), $this->formSet($state));

        $this->assertSame('Keep', $state['location_name']);
    }

    #[Test]
    public function clear_resets_location_state(): void
    {
        $state = [
            'location_name' => 'Berlin',
            'latitude' => 1.0,
            'longitude' => 2.0,
            'selected_location' => '1',
            'location_search_results' => [['place_id' => '1']],
            'location_search' => 'Berlin',
        ];

        LocationPicker::clear($this->formSet($state));

        $this->assertNull($state['location_name']);
        $this->assertNull($state['latitude']);
        $this->assertNull($state['longitude']);
        $this->assertNull($state['selected_location']);
        $this->assertSame([], $state['location_search_results']);
        $this->assertNull($state['location_search']);
    }

    private function formGet(array &$state): Get
    {
        $get = Mockery::mock(Get::class);
        $get->shouldReceive('__invoke')->andReturnUsing(
            fn (string $key) => $state[$key] ?? null
        );

        return $get;
    }

    private function formSet(array &$state): Set
    {
        $set = Mockery::mock(Set::class);
        $set->shouldReceive('__invoke')->andReturnUsing(
            function (string $key, mixed $value) use (&$state): void {
                $state[$key] = $value;
            }
        );

        return $set;
    }
}
