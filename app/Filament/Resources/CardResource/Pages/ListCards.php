<?php

namespace App\Filament\Resources\CardResource\Pages;

use App\Filament\Resources\CardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCards extends ListRecords
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
            CardResource\Widgets\SpentPayingSaving::class,
        ];
    }
}
