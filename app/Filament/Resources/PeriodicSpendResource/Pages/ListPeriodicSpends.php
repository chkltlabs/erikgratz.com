<?php

namespace App\Filament\Resources\PeriodicSpendResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PeriodicSpendResource\Widgets\SpendOverTimeChart;
use App\Filament\Resources\PeriodicSpendResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeriodicSpends extends ListRecords
{
    protected static string $resource = PeriodicSpendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SpendOverTimeChart::class,
        ];
    }
}
