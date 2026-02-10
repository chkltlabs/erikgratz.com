<?php

namespace Tests\Feature\Filament\Parent;

use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class InfolistRelationManager extends FilamentTestCase
{
    use DatabaseTransactions;

    public static string $ownerModel = '';

    public static string $relationship = '';

    public static string $relationManager = '';

    public function test_can_list_related_records()
    {
        $owner = static::$ownerModel::factory()->create();
        $records = static::$ownerModel::factory()
            ->has(static::$relationship::factory()->count(3))
            ->create()
            ->{static::$relationship};

        Livewire::test(static::$relationManager, [
            'ownerRecord' => $owner,
            'pageClass' => get_class($owner),
        ])
            ->assertCanSeeTableRecords($owner->{static::$relationship});
    }

    public function test_can_view_related_record()
    {
        $owner = static::$ownerModel::factory()
            ->has(static::$relationship::factory()->count(1))
            ->create();

        $record = $owner->{static::$relationship}->first();

        Livewire::test(static::$relationManager, [
            'ownerRecord' => $owner,
            'pageClass' => get_class($owner),
        ])
            ->callTableAction('view', $record)
            ->assertSchemaStateSet($this->getInfolistAttributes($record), 'infolist');
    }

    abstract protected function getInfolistAttributes($record): array;
}
