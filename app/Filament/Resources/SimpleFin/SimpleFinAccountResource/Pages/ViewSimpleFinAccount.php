<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinAccountResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSimpleFinAccount extends ViewRecord
{
    protected static string $resource = SimpleFinAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
