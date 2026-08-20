<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ActivityResource\Widgets\ActivityMap;
use App\Filament\Resources\ActivityResource\Widgets\ActivityTimelineChart;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    use ExposesTableToWidgets;

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
            ActivityTimelineChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ActivityMap::class,
        ];
    }
}
