<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LoanAgainstSavingsResource;
use App\Models\LoanAgainstSavings;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages\ListLoanAgainstSavings;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages\CreateLoanAgainstSavings;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages\EditLoanAgainstSavings;
use Tests\Feature\Filament\Parent\Resource;

class LoanAgainstSavingsTest extends Resource
{
    public static string $resourceClass = LoanAgainstSavingsResource::class;
    public static string $modelClass = LoanAgainstSavings::class;
    public static string $listPage = ListLoanAgainstSavings::class;
    public static string $createPage = CreateLoanAgainstSavings::class;
    public static string $editPage = EditLoanAgainstSavings::class;

    public static array $unsetAttributesBeforeCompare = ['user_id', 'balance', 'payments'];
}
