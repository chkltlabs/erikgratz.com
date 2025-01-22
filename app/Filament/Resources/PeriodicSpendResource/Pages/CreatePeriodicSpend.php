<?php

namespace App\Filament\Resources\PeriodicSpendResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\PeriodicSpendResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePeriodicSpend extends CreateRecord
{
    protected static string $resource = PeriodicSpendResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ActivityResource::splitStartEndDate($data);
    }
}
