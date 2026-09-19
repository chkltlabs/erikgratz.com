<?php

namespace App\Filament\Resources\LoyaltyMembershipResource\Pages;

use App\Filament\Resources\LoyaltyMembershipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyMemberships extends ListRecords
{
    protected static string $resource = LoyaltyMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
