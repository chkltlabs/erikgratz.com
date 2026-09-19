<?php

namespace App\Filament\Resources\LoyaltyMembershipResource\Pages;

use App\Filament\Resources\LoyaltyMembershipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyMembership extends EditRecord
{
    protected static string $resource = LoyaltyMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
