<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinAccountResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSimpleFinAccount extends EditRecord
{
    protected static string $resource = SimpleFinAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
