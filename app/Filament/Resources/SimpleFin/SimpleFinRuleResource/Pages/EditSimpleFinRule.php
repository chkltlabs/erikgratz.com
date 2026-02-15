<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinRuleResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSimpleFinRule extends EditRecord
{
    protected static string $resource = SimpleFinRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
