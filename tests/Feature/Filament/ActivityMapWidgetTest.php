<?php

namespace Tests\Feature\Filament;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Widgets\ActivityMap;
use App\Models\Activity;
use App\Models\User;
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
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-10',
        ]);

        Livewire::test(ActivityMap::class)
            ->assertSuccessful()
            ->assertSee('Travel map')
            ->assertSee('Toronto');
    }
}
