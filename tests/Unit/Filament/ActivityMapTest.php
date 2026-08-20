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
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-05',
                'travel_method' => null,
            ]),
            new Activity([
                'name' => 'B',
                'latitude' => 41.0,
                'longitude' => 28.0,
                'start_date' => '2024-02-01',
                'end_date' => '2024-02-10',
                'travel_method' => TravelMethod::Plane,
            ]),
            new Activity([
                'name' => 'C',
                'latitude' => 48.0,
                'longitude' => 2.0,
                'start_date' => '2024-03-01',
                'end_date' => '2024-03-10',
                'travel_method' => TravelMethod::Train,
            ]),
        ]);

        $routes = ActivityMap::buildRoutes($activities);

        $this->assertCount(2, $routes);
        $this->assertSame(TravelMethod::Plane, $routes[0]['method']);
        $this->assertFalse($routes[0]['is_return']);
        $this->assertSame(TravelMethod::colors()[TravelMethod::Plane], $routes[0]['color']);
        $this->assertSame(TravelMethod::Train, $routes[1]['method']);
        $this->assertFalse($routes[1]['is_return']);
        $this->assertSame(40.0, $routes[0]['from']['lat']);
        $this->assertSame(41.0, $routes[0]['to']['lat']);
    }

    #[Test]
    public function it_draws_return_to_base_when_vacation_ends_before_base(): void
    {
        $base = new Activity([
            'id' => 1,
            'name' => 'Home base',
            'latitude' => 40.0,
            'longitude' => -74.0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'travel_method' => null,
        ]);
        $vacation = new Activity([
            'id' => 2,
            'name' => 'Paris',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'start_date' => '2024-01-05',
            'end_date' => '2024-01-12',
            'travel_method' => TravelMethod::Plane,
        ]);

        $routes = ActivityMap::buildRoutes(new Collection([$base, $vacation]));

        $this->assertCount(2, $routes);
        $this->assertFalse($routes[0]['is_return']);
        $this->assertSame(40.0, $routes[0]['from']['lat']);
        $this->assertSame(48.0, $routes[0]['to']['lat']);

        $this->assertTrue($routes[1]['is_return']);
        $this->assertSame(48.0, $routes[1]['from']['lat']);
        $this->assertSame(40.0, $routes[1]['to']['lat']);
        $this->assertSame(TravelMethod::Plane, $routes[1]['method']);
    }

    #[Test]
    public function it_skips_return_while_a_later_stop_is_still_nested_in_base(): void
    {
        $base = new Activity([
            'id' => 1,
            'name' => 'Home base',
            'latitude' => 40.0,
            'longitude' => -74.0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        $paris = new Activity([
            'id' => 2,
            'name' => 'Paris',
            'latitude' => 48.0,
            'longitude' => 2.0,
            'start_date' => '2024-01-05',
            'end_date' => '2024-01-10',
            'travel_method' => TravelMethod::Plane,
        ]);
        $london = new Activity([
            'id' => 3,
            'name' => 'London',
            'latitude' => 51.5,
            'longitude' => -0.1,
            'start_date' => '2024-01-10',
            'end_date' => '2024-01-15',
            'travel_method' => TravelMethod::Train,
        ]);

        $routes = ActivityMap::buildRoutes(new Collection([$base, $paris, $london]));

        $returns = array_values(array_filter($routes, fn (array $route): bool => $route['is_return']));

        $this->assertCount(1, $returns);
        $this->assertSame(51.5, $returns[0]['from']['lat']);
        $this->assertSame(40.0, $returns[0]['to']['lat']);
        $this->assertSame(0, ActivityMap::findEnclosingBaseIndex(new Collection([$base, $paris, $london]), 2));
        $this->assertTrue(ActivityMap::shouldDrawReturnToBase(new Collection([$base, $paris, $london]), 2));
        $this->assertFalse(ActivityMap::shouldDrawReturnToBase(new Collection([$base, $paris, $london]), 1));
    }

    #[Test]
    public function it_only_returns_from_last_same_day_nested_geotag(): void
    {
        $base = new Activity([
            'id' => 1,
            'name' => 'Home base',
            'latitude' => 40.0,
            'longitude' => -74.0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        $spotA = new Activity([
            'id' => 2,
            'name' => 'Rome A',
            'latitude' => 41.9,
            'longitude' => 12.5,
            'start_date' => '2024-01-08',
            'end_date' => '2024-01-08',
            'travel_method' => TravelMethod::Plane,
        ]);
        $spotB = new Activity([
            'id' => 3,
            'name' => 'Rome B',
            'latitude' => 41.91,
            'longitude' => 12.51,
            'start_date' => '2024-01-08',
            'end_date' => '2024-01-09',
            'travel_method' => TravelMethod::Car,
        ]);

        $items = new Collection([$base, $spotA, $spotB]);

        $this->assertFalse(ActivityMap::shouldDrawReturnToBase($items, 1));
        $this->assertTrue(ActivityMap::shouldDrawReturnToBase($items, 2));
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
