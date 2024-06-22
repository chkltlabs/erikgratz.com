<?php

namespace App\Filament\SpendResource\Pages;

use App\Filament\SpendResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpends extends ListRecords
{
    protected static string $resource = SpendResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
