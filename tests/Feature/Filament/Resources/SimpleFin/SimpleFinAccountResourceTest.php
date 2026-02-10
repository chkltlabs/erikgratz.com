<?php

namespace Tests\Feature\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinAccountResource;
use App\Models\SimpleFin\SimpleFinAccount;
use Tests\Feature\Filament\Parent\InfolistResource;

class SimpleFinAccountResourceTest extends InfolistResource
{
    public static string $resource = SimpleFinAccountResource::class;

    public static string $indexPage = SimpleFinAccountResource\Pages\ListSimpleFinAccounts::class;

    public static string $viewPage = SimpleFinAccountResource\Pages\ViewSimpleFinAccount::class;

    public static string $model = SimpleFinAccount::class;

    protected function getInfolistAttributes($record): array
    {
        return [
            'name' => $record->name,
            'user.name' => $record->user->name,
            'organization.name' => $record->organization->name,
            'currency' => $record->currency,
            'balance' => $record->balance,
            'available_balance' => $record->available_balance,
            'balance_date' => $record->balance_date->format('Y-m-d H:i:s'),
        ];
    }
}
