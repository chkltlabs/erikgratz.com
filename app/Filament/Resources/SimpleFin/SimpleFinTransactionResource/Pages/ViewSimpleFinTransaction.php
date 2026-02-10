<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinTransactionResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSimpleFinTransaction extends ViewRecord
{
    protected static string $resource = SimpleFinTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
