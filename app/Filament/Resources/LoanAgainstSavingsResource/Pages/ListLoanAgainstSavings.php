<?php

namespace App\Filament\Resources\LoanAgainstSavingsResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LoanAgainstSavingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanAgainstSavings extends ListRecords
{
    protected static string $resource = LoanAgainstSavingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
