<?php

namespace Tests\Feature\Filament;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Widgets\ActivityMap;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityMapWidgetTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::first() ?? User::factory()->create());
    }

    #[Test]
    public function list_page_registers_map_as_footer_widget(): void
    {
        $page = new ListActivities;
        $method = new \ReflectionMethod(ListActivities::class, 'getFooterWidgets');
        $method->setAccessible(true);

        $this->assertSame([ActivityMap::class], $method->invoke($page));
        $this->assertTrue(ActivityMap::isLazy());
        $this->assertContains(
            \Filament\Pages\Concerns\ExposesTableToWidgets::class,
            class_uses_recursive(ListActivities::class),
        );
    }

    #[Test]
    public function map_widget_renders_with_located_activities(): void
    {
        Activity::factory()->withLocation(
            name: 'Toronto, Canada',
            latitude: 43.65,
            longitude: -79.38,
            travelMethod: TravelMethod::Plane,
        )->create([
            'name' => 'Toronto',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        Livewire::test(ActivityMap::class)
            ->assertSuccessful()
            ->assertSee('Travel map')
            ->assertSee('Toronto');
    }

    #[Test]
    public function map_respects_default_not_archived_table_filter(): void
    {
        $grace = Activity::ARCHIVE_DAY_GRACE;

        Activity::factory()->withLocation(
            name: 'Current City',
            latitude: 40.0,
            longitude: -74.0,
            travelMethod: TravelMethod::Plane,
        )->create([
            'name' => 'Current Trip',
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        Activity::factory()->withLocation(
            name: 'Archived City',
            latitude: 48.0,
            longitude: 2.0,
            travelMethod: TravelMethod::Train,
        )->create([
            'name' => 'Archived Trip',
            'start_date' => Carbon::now()->subDays($grace + 40)->toDateString(),
            'end_date' => Carbon::now()->subDays($grace + 20)->toDateString(),
        ]);

        Livewire::test(ActivityMap::class)
            ->assertSuccessful()
            ->assertSee('Current Trip')
            ->assertDontSee('Archived Trip');
    }

    #[Test]
    public function map_updates_when_archived_filter_includes_all(): void
    {
        $grace = Activity::ARCHIVE_DAY_GRACE;

        Activity::factory()->withLocation(
            name: 'Archived City',
            latitude: 48.0,
            longitude: 2.0,
            travelMethod: TravelMethod::Train,
        )->create([
            'name' => 'Archived Trip',
            'start_date' => Carbon::now()->subDays($grace + 40)->toDateString(),
            'end_date' => Carbon::now()->subDays($grace + 20)->toDateString(),
        ]);

        Livewire::test(ActivityMap::class, [
            'tableFilters' => [
                'archived' => ['value' => null],
            ],
        ])
            ->assertSuccessful()
            ->assertSee('Archived Trip');
    }

    #[Test]
    public function map_respects_travel_method_table_filter(): void
    {
        Activity::factory()->withLocation(
            name: 'Plane City',
            latitude: 40.0,
            longitude: -74.0,
            travelMethod: TravelMethod::Plane,
        )->create([
            'name' => 'Plane Trip',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        Activity::factory()->withLocation(
            name: 'Train City',
            latitude: 48.0,
            longitude: 2.0,
            travelMethod: TravelMethod::Train,
        )->create([
            'name' => 'Train Trip',
            'start_date' => now()->subDays(4)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        Livewire::test(ActivityMap::class, [
            'tableFilters' => [
                'archived' => ['value' => false],
                'travel_method' => ['value' => TravelMethod::Plane],
            ],
        ])
            ->assertSuccessful()
            ->assertSee('Plane Trip')
            ->assertDontSee('Train Trip');
    }

    #[Test]
    public function list_page_passes_table_filters_through_to_map_widget(): void
    {
        Activity::factory()->withLocation(
            name: 'Plane City',
            latitude: 40.0,
            longitude: -74.0,
            travelMethod: TravelMethod::Plane,
        )->create([
            'name' => 'Plane Trip',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        Activity::factory()->withLocation(
            name: 'Ferry City',
            latitude: 41.0,
            longitude: 29.0,
            travelMethod: TravelMethod::Ferry,
        )->create([
            'name' => 'Ferry Trip',
            'start_date' => now()->subDays(4)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $list = Livewire::test(ListActivities::class)
            ->filterTable('travel_method', TravelMethod::Ferry)
            ->assertCanSeeTableRecords(Activity::query()->where('name', 'Ferry Trip')->get())
            ->assertCanNotSeeTableRecords(Activity::query()->where('name', 'Plane Trip')->get());

        Livewire::test(ActivityMap::class, $list->instance()->getWidgetData())
            ->assertSuccessful()
            ->assertSee('Ferry Trip')
            ->assertDontSee('Plane Trip');
    }
}
