<?php

namespace App\Filament\Resources\CardResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\CardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCard extends EditRecord
{
    protected static string $resource = CardResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
