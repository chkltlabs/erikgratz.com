<?php

namespace Tests\Feature\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinRuleResource;
use App\Models\SimpleFinRule;
use App\Filament\Resources\SimpleFin\SimpleFinRuleResource\Pages\ListSimpleFinRules;
use App\Filament\Resources\SimpleFin\SimpleFinRuleResource\Pages\CreateSimpleFinRule;
use App\Filament\Resources\SimpleFin\SimpleFinRuleResource\Pages\EditSimpleFinRule;
use Tests\Feature\Filament\Parent\Resource;

class SimpleFinRuleResourceTest extends Resource
{
    public static string $resourceClass = SimpleFinRuleResource::class;
    public static string $modelClass = SimpleFinRule::class;
    public static string $listPage = ListSimpleFinRules::class;
    public static string $createPage = CreateSimpleFinRule::class;
    public static string $editPage = EditSimpleFinRule::class;
}
