<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinRuleResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSimpleFinRules extends ListRecords
{
    protected static string $resource = SimpleFinRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
