<?php

namespace App\Filament\Resources\PromoSourceItemResource\Pages;

use App\Filament\Resources\PromoSourceItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromoSourceItem extends EditRecord
{
    protected static string $resource = PromoSourceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
