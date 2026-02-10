<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSimpleFinOrganization extends ViewRecord
{
    protected static string $resource = SimpleFinOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
