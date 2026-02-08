<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PeriodicSpendResource;
use App\Models\PeriodicSpend;
use App\Filament\Resources\PeriodicSpendResource\Pages\ListPeriodicSpends;
use App\Filament\Resources\PeriodicSpendResource\Pages\CreatePeriodicSpend;
use App\Filament\Resources\PeriodicSpendResource\Pages\EditPeriodicSpend;
use Tests\Feature\Filament\Parent\Resource;

class PeriodicSpendTest extends Resource
{
    public static string $resourceClass = PeriodicSpendResource::class;
    public static string $modelClass = PeriodicSpend::class;
    public static string $listPage = ListPeriodicSpends::class;
    public static string $createPage = CreatePeriodicSpend::class;
    public static string $editPage = EditPeriodicSpend::class;

    public static array $unsetAttributesBeforeCompare = ['start_date', 'end_date', 'user_id', 'payments', 'is_income', 'period'];
}
