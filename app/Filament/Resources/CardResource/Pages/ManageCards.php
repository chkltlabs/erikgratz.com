<?php

namespace App\Filament\Resources\CardResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Filament\Resources\CardResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCards extends ManageRecords
{
    protected static string $resource = CardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SpentPayingSaving::class,
        ];
    }
}
