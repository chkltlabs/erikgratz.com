<?php

namespace App\Filament\Resources\SpendResource\Pages;

use App\Filament\Resources\SpendResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpend extends CreateRecord
{
    protected static string $resource = SpendResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
