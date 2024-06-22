<?php

namespace App\Filament\SpendResource\Pages;

use App\Filament\SpendResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpend extends CreateRecord
{
    protected static string $resource = SpendResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }
}
