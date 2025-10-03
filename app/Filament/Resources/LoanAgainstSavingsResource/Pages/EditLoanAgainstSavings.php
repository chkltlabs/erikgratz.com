<?php

namespace App\Filament\Resources\LoanAgainstSavingsResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\LoanAgainstSavingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanAgainstSavings extends EditRecord
{
    protected static string $resource = LoanAgainstSavingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
