<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ActivityResource\Widgets\ActivityBarChart;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityBarChart::class,
        ];
    }
}
