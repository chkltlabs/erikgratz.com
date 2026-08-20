<?php

namespace Tests\Unit\Filament;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource\Widgets\ActivityMap;
use App\Models\Activity;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityMapTest extends TestCase
{
    #[Test]
    public function it_builds_chronological_routes_colored_by_arrival_method(): void
    {
        $activities = new Collection([
            new Activity([
                'name' => 'A',
                'latitude' => 40.0,
                'longitude' => -74.0,
                'travel_method' => null,
            ]),
            new Activity([
                'name' => 'B',
                'latitude' => 41.0,
                'longitude' => 28.0,
                'travel_method' => TravelMethod::Plane,
            ]),
            new Activity([
                'name' => 'C',
                'latitude' => 48.0,
                'longitude' => 2.0,
                'travel_method' => TravelMethod::Train,
            ]),
        ]);

        $routes = ActivityMap::buildRoutes($activities);

        $this->assertCount(2, $routes);
        $this->assertSame(TravelMethod::Plane, $routes[0]['method']);
        $this->assertSame(TravelMethod::colors()[TravelMethod::Plane], $routes[0]['color']);
        $this->assertSame(TravelMethod::Train, $routes[1]['method']);
        $this->assertSame(TravelMethod::colors()[TravelMethod::Train], $routes[1]['color']);
        $this->assertSame(40.0, $routes[0]['from']['lat']);
        $this->assertSame(41.0, $routes[0]['to']['lat']);
    }

    #[Test]
    public function it_formats_points_and_skips_route_building_for_single_location(): void
    {
        $activities = new Collection([
            new Activity([
                'id' => 7,
                'name' => 'Solo',
                'location_name' => 'Lisbon',
                'latitude' => 38.7,
                'longitude' => -9.1,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-05',
                'travel_method' => TravelMethod::Ferry,
            ]),
        ]);

        $points = ActivityMap::formatPoints($activities);
        $routes = ActivityMap::buildRoutes($activities);

        $this->assertCount(1, $points);
        $this->assertSame('Solo', $points[0]['name']);
        $this->assertSame('Lisbon', $points[0]['location']);
        $this->assertSame(TravelMethod::Ferry, $points[0]['travel_method']);
        $this->assertSame([], $routes);
    }

    #[Test]
    public function legend_includes_all_travel_methods(): void
    {
        $legend = ActivityMap::legend();

        $this->assertSame(
            [TravelMethod::Plane, TravelMethod::Car, TravelMethod::Train, TravelMethod::Ferry],
            array_column($legend, 'value')
        );
    }
}
