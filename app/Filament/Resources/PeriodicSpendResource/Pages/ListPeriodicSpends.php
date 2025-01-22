<?php

namespace App\Filament\Resources\PeriodicSpendResource\Pages;

use App\Filament\Resources\PeriodicSpendResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeriodicSpends extends ListRecords
{
    protected static string $resource = PeriodicSpendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
