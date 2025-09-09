<?php

namespace Tests\Feature\Filament\Parent;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;
use Tests\Feature\Filament\Traits\PrepsForComparison;

class RelationManager extends FilamentTestCase
{
    use PrepsForComparison;

    public static string $relationManagerClass = ''; // must be overridden

    public static string $modelClass = ''; // must be overridden

    public static string $parentModelClass = ''; // must be overridden

    public static string $relationName = ''; // must be overridden

    public static string $editPage = ''; // must be overridden

    public static int $count = 4; // optionally overridden

    public function test_relation_manager_lists()
    {
        //        Schema::disableForeignKeyConstraints();
        //        static::$modelClass::query()->delete();
        //        Schema::enableForeignKeyConstraints();

        $model = static::$parentModelClass::factory()
            ->has(static::$modelClass::factory()->count(static::$count), static::$relationName)
            ->create();
        $relations = $model->{static::$relationName};

        Livewire::test(static::$relationManagerClass, [
            'ownerRecord' => $model,
            'pageClass' => static::$editPage,
        ])
            ->set('tableRecordsPerPage', 'all')
            ->assertCanSeeTableRecords($relations);
    }

    public function test_relation_manager_can_create()
    {
        $owner = static::$parentModelClass::factory()
            ->has(static::$modelClass::factory()->count(static::$count), static::$relationName)
            ->create();
        $new = static::$modelClass::factory()->recycle($owner)->make();
        $preCount = $owner->{static::$relationName}()->count();
        Livewire::test(static::$relationManagerClass, [
            'ownerRecord' => $owner,
            'pageClass' => static::$editPage,
        ])
            ->callTableAction(CreateAction::class, null, $new->toArray())
            ->assertHasNoTableActionErrors();

        $this->assertEquals($preCount + 1, $owner->{static::$relationName}()->count());
    }

    public function test_relation_manager_can_edit()
    {
        $owner = static::$parentModelClass::factory()
            ->has(static::$modelClass::factory()->count(static::$count), static::$relationName)
            ->create();
        $model = $owner->{static::$relationName}->random();
        $new = static::$modelClass::factory()->recycle($owner)->make();

        Livewire::test(static::$relationManagerClass, [
            'ownerRecord' => $owner,
            'pageClass' => static::$editPage,
        ])
            ->callTableAction(EditAction::class, $model, $new->toArray())
            ->assertHasNoTableActionErrors();

        self::assertModelsEqual($model, $new);

    }

    public function test_relation_manager_can_delete()
    {
        $owner = static::$parentModelClass::factory()
            ->has(static::$modelClass::factory()->count(static::$count), static::$relationName)
            ->create();
        $deletable = $owner->{static::$relationName}->random();

        Livewire::test(static::$relationManagerClass, [
            'ownerRecord' => $owner,
            'pageClass' => static::$editPage,
        ])
            ->callTableBulkAction(DeleteBulkAction::class, [$deletable])
            ->assertHasNoTableActionErrors();

        if (method_exists($deletable, 'bootSoftDeletes')) {
            // the model does softdeletes, so modelMissing will still find it
            $deletable->refresh();
            $this->assertNotNull($deletable->deleted_at);
        } else {
            $this->assertModelMissing($deletable);
        }
    }
}
