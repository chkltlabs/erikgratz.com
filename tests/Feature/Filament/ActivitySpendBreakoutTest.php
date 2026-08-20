<?php

namespace Tests\Feature\Filament;

use App\Enums\TravelMethod;
use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Spend;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ActivitySpendBreakoutTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::first() ?? User::factory()->create());
    }

    public function test_can_break_out_spends_into_new_activity()
    {
        $oldActivity = Activity::factory()->create([
            'name' => 'Old Activity',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-31',
        ]);

        $spends = Spend::factory()->count(2)->create([
            'activity_id' => $oldActivity->id,
        ]);

        $newActivityData = [
            'name' => 'New Activity',
            'start_end_date' => '01/02/2023 - 28/02/2023',
            'description' => 'New activity description',
            'travel_method' => TravelMethod::Train,
            'location_name' => 'Montreal, Canada',
            'latitude' => 45.5017,
            'longitude' => -73.5673,
        ];

        Livewire::test(ActivityResource\RelationManagers\SpendsRelationManager::class, [
            'ownerRecord' => $oldActivity,
            'pageClass' => ActivityResource\Pages\EditActivity::class,
        ])
            ->callTableBulkAction('break_out', $spends, $newActivityData)
            ->assertHasNoTableBulkActionErrors();

        $this->assertDatabaseHas('activities', [
            'name' => 'New Activity',
            'description' => 'New activity description',
            'start_date' => '2023-02-01',
            'end_date' => '2023-02-28',
            'travel_method' => TravelMethod::Train,
            'location_name' => 'Montreal, Canada',
        ]);

        $newActivity = Activity::where('name', 'New Activity')->first();

        foreach ($spends as $spend) {
            $this->assertDatabaseHas('spends', [
                'id' => $spend->id,
                'activity_id' => $newActivity->id,
            ]);
        }
    }
}
