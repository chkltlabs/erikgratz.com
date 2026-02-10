<?php

namespace Tests\Feature\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource;
use App\Models\SimpleFin\SimpleFinOrganization;
use Tests\Feature\Filament\Parent\InfolistResource;

class SimpleFinOrganizationResourceTest extends InfolistResource
{
    public static string $resource = SimpleFinOrganizationResource::class;

    public static string $indexPage = SimpleFinOrganizationResource\Pages\ListSimpleFinOrganizations::class;

    public static string $viewPage = SimpleFinOrganizationResource\Pages\ViewSimpleFinOrganization::class;

    public static string $model = SimpleFinOrganization::class;

    protected function getInfolistAttributes($record): array
    {
        return [
            'name' => $record->name,
            'domain' => $record->domain,
            'url' => $record->url,
            'sfin_url' => $record->sfin_url,
        ];
    }
}
