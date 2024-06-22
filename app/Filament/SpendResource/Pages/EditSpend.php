<?php

namespace App\Filament\SpendResource\Pages;

use App\Filament\SpendResource;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpend extends EditRecord
{
    protected static string $resource = SpendResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
