<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinTransactionResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSimpleFinTransactions extends ListRecords
{
    protected static string $resource = SimpleFinTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
