<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinTransactionResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSimpleFinTransaction extends EditRecord
{
    protected static string $resource = SimpleFinTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
