<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\Pages;

use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSimpleFinOrganization extends EditRecord
{
    protected static string $resource = SimpleFinOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
