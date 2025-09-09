<?php

namespace Tests\Feature\Filament\Parent;

use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;
use Tests\Feature\Filament\Traits\PrepsForComparison;

class SimpleResource extends FilamentTestCase
{
    use PrepsForComparison;

    public static string $modelClass = ''; // must be overridden

    public static string $pageClass = ''; // must be overridden

    public function test_simple_resource_lists()
    {
        Schema::disableForeignKeyConstraints();
        static::$modelClass::query()->delete();
        Schema::enableForeignKeyConstraints();

        $model = static::$modelClass::factory()->count(2)->create();

        Livewire::test(static::$pageClass)
            ->assertCanSeeTableRecords($model);
    }

    public function test_simple_resource_can_create()
    {
        $model = static::$modelClass::factory()->make();

        Livewire::test(static::$pageClass)
            ->callAction('create', $model->toArray())
            ->assertHasNoActionErrors();

        self::assertNotNull($model->refresh());
    }

    public function test_simple_resource_can_edit()
    {
        $model = static::$modelClass::factory()->create();
        $new = static::$modelClass::factory()->make();

        Livewire::test(static::$pageClass)
            ->callTableAction('edit', $model, $new->toArray())
            ->assertHasNoActionErrors();

        self::assertModelsEqual($model, $new);
    }

    public function test_simple_resource_can_delete()
    {
        $model = static::$modelClass::factory()->create();

        Livewire::test(static::$pageClass)
            ->callTableBulkAction('delete', [$model])
            ->assertHasNoActionErrors();

        if (method_exists($model, 'bootSoftDeletes')) {
            // the model does softdeletes, so modelMissing will still find it
            $model->refresh();
            $this->assertNotNull($model->deleted_at);
        } else {
            $this->assertModelMissing($model);
        }
    }
}
