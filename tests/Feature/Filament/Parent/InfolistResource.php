<?php

namespace Tests\Feature\Filament\Parent;

use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class InfolistResource extends FilamentTestCase
{
    use DatabaseTransactions;

    public static string $resource = '';

    public static string $indexPage = '';

    public static string $viewPage = '';

    public static string $model = '';

    public function test_index_renders()
    {
        $this->get(static::$resource::getUrl('index'))->assertSuccessful();
    }

    public function test_view_renders()
    {
        $record = static::$model::factory()->create();

        $this->get(static::$resource::getUrl('view', [
            'record' => $record,
        ]))->assertSuccessful();
    }

    public function test_create_renders()
    {
        $this->get(static::$resource::getUrl('create'))->assertSuccessful();
    }

    public function test_edit_renders()
    {
        $record = static::$model::factory()->create();
        $this->get(static::$resource::getUrl('edit', [
            'record' => $record,
        ]))->assertSuccessful();
    }

    public function test_can_list_records()
    {
        $records = static::$model::factory()->count(3)->create();

        Livewire::test(static::$indexPage)
            ->assertCanSeeTableRecords($records);
    }

    public function test_can_view_record()
    {
        $record = static::$model::factory()->create();

        $test = Livewire::test(static::$viewPage, [
            'record' => $record->getKey(),
        ]);

        foreach ($this->getInfolistAttributes($record) as $name => $value) {
            $test->assertSchemaStateSet([$name => $value], 'infolist');
        }
    }

    abstract protected function getInfolistAttributes($record): array;
}
