<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\SpendResource;
use App\Models\Activity;
use App\Models\Spend;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::first() ?? User::factory()->create());
    }

    //-------------------------------
    //Activities - Resource
    //-------------------------------

    public function test_activity_index_renders()
    {
        $this->get(ActivityResource::getUrl('index'))->assertSuccessful();
    }

    public function test_activity_create_renders()
    {
        $this->get(ActivityResource::getUrl('create'))->assertSuccessful();
    }

    public function test_activity_edit_renders()
    {
        $this->get(ActivityResource::getUrl('edit', [
            'record' => Activity::factory()->create(),
        ]))->assertSuccessful();
    }

    public function test_activity_can_list()
    {
        Activity::all()->each->delete();
        $model = Activity::factory()->count(2)->create();

        Livewire::test(ActivityResource\Pages\ListActivities::class)
            ->filterTable('archived', null)
            ->assertCanSeeTableRecords($model);
    }

    private static function filler(array $data): array
    {
        return (new ActivityResource\Pages\EditActivity())->mutateFormDataBeforeFill($data);
    }

    public function test_activity_can_create()
    {
        $model = Activity::factory()->make();
        Livewire::test(ActivityResource\Pages\CreateActivity::class)
            ->fillForm(self::filler($model->toArray()))
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_activity_edit_fills_form()
    {
        $model = Activity::factory()->create();
        Livewire::test(ActivityResource\Pages\EditActivity::class, [
            'record' => $model->id,
        ])
            ->assertFormSet($model->toArray());
    }

    public function test_activity_can_edit()
    {
        $model = Activity::factory()->create();
        $new = Activity::factory()->make();
        Livewire::test(ActivityResource\Pages\EditActivity::class, [
            'record' => $model->id,
        ])
            ->fillForm(self::filler($new->toArray()))
            ->call('save')
            ->assertHasNoFormErrors();
        $arr = $new->toArray();
        $this->assertDatabaseHas($new->getTable(), $arr);
    }

    public function test_activity_can_delete()
    {
        $model = Activity::factory()->create();
        Livewire::test(ActivityResource\Pages\EditActivity::class, [
            'record' => $model->id,
        ])
            ->callAction('delete')
            ->assertHasNoFormErrors();
        $this->assertDatabaseMissing($model->getTable(), $model->toArray());
    }

    //-------------------------------
    //Spends - Resource
    //-------------------------------

    public function test_spend_index_renders()
    {
        $this->get(SpendResource::getUrl('index'))->assertSuccessful();
    }

    public function test_spend_create_renders()
    {
        $this->get(SpendResource::getUrl('create'))->assertSuccessful();
    }

    public function test_spend_edit_renders()
    {
        $this->get(SpendResource::getUrl('edit', [
            'record' => Spend::factory()->create(),
        ]))->assertSuccessful();
    }

    public function test_spend_can_list()
    {
        Spend::all()->each->delete();
        $model = Spend::factory()->count(2)->create();

        Livewire::test(SpendResource\Pages\ListSpends::class)
            ->assertCanSeeTableRecords($model);
    }

    public function test_spend_can_create()
    {
        $model = Spend::factory()->make();
        Livewire::test(SpendResource\Pages\CreateSpend::class)
            ->fillForm($model->toArray())
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_spend_edit_fills_form()
    {
        $model = Spend::factory()->create();
        Livewire::test(SpendResource\Pages\EditSpend::class, [
            'record' => $model->id,
        ])
            ->assertFormSet($model->toArray());
    }

    public function test_spend_can_edit()
    {
        $model = Spend::factory()->create();
        $new = Spend::factory()->make();
        Livewire::test(SpendResource\Pages\EditSpend::class, [
            'record' => $model->id,
        ])
            ->fillForm($new->toArray())
            ->call('save')
            ->assertHasNoFormErrors();
        $arr = $new->toArray();
        $this->assertDatabaseHas($new->getTable(), $arr);
    }

    public function test_spend_can_delete()
    {
        $model = Spend::factory()->create();
        Livewire::test(SpendResource\Pages\EditSpend::class, [
            'record' => $model->id,
        ])
            ->callAction('delete')
            ->assertHasNoFormErrors();
        $this->assertDatabaseMissing($model->getTable(), $model->toArray());
    }
}
