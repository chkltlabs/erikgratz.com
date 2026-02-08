<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SpendResource;
use App\Models\Spend;
use App\Filament\Resources\SpendResource\Pages\ListSpends;
use App\Filament\Resources\SpendResource\Pages\CreateSpend;
use App\Filament\Resources\SpendResource\Pages\EditSpend;
use Tests\Feature\Filament\Parent\Resource;

class SpendTest extends Resource
{
    public static string $resourceClass = SpendResource::class;
    public static string $modelClass = Spend::class;
    public static string $listPage = ListSpends::class;
    public static string $createPage = CreateSpend::class;
    public static string $editPage = EditSpend::class;

    public static array $unsetAttributesBeforeCompare = ['user_id', 'payments'];
}
