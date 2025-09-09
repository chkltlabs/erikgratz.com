<?php

namespace Tests\Feature\Filament\Parent;

use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;
use Tests\Feature\Filament\Traits\PrepsForComparison;

class Resource extends FilamentTestCase
{
    use PrepsForComparison;

    public static string $resourceClass = ''; // must be overridden

    public static string $modelClass = ''; // must be overridden

    public static string $listPage = ''; // must be overridden

    public static string $createPage = ''; // must be overridden

    public static string $editPage = ''; // must be overridden

    protected function setUp(): void
    {
        parent::setUp();
        Repeater::fake();
    }

    public function test_resource_index_renders()
    {
        $this->get(static::$resourceClass::getUrl('index'))->assertSuccessful();
    }

    public function test_resource_create_renders()
    {
        $this->get(static::$resourceClass::getUrl('create'))->assertSuccessful();
    }

    public function test_resource_edit_renders()
    {
        $this->get(static::$resourceClass::getUrl('edit', [
            'record' => static::$modelClass::factory()->create()->getKey(),
        ]))->assertSuccessful();
    }

    public function test_resource_can_list()
    {
        //        Schema::disableForeignKeyConstraints();
        //        static::$modelClass::query()->delete();
        //        Schema::enableForeignKeyConstraints();

        $model = static::$modelClass::factory()->count(2)->create();

        Livewire::test(static::$listPage)
            ->set('tableRecordsPerPage', 'all')
            ->assertCanSeeTableRecords($model);
    }

    public function test_resource_can_create()
    {
        $model = static::$modelClass::factory()->make();
        Livewire::test(static::$createPage)
            ->fillForm($model->toArray())
            ->call('create')
            ->assertHasNoFormErrors();

        self::assertNotNull($model->refresh());
    }

    public function test_resource_edit_fills_form()
    {
        $model = static::$modelClass::factory()->create();
        Livewire::test(static::$editPage, [
            'record' => $model->getKey(),
        ])
            ->assertFormSet($model->toArray());
    }

    public function test_resource_can_edit()
    {
        $model = static::$modelClass::factory()->create();
        $new = static::$modelClass::factory()->make();
        $new->{$new->getKeyName()} = $model->getKey();
        Livewire::test(static::$editPage, [
            'record' => $model->getKey(),
        ])
            ->fillForm($new->toArray())
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertModelsEqual($model, $new);
    }

    public function test_resource_can_delete()
    {
        $model = static::$modelClass::factory()->create();
        Livewire::test(static::$editPage, [
            'record' => $model->getKey(),
        ])
            ->callAction('delete')
            ->assertHasNoFormErrors();

        if (method_exists($model, 'bootSoftDeletes')) {
            // the model does softdeletes, so modelMissing will still find it
            $model->refresh();
            $this->assertNotNull($model->deleted_at);
        } else {
            $this->assertModelMissing($model);
        }
    }
}
