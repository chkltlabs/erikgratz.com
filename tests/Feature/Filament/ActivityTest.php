<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Pages\CreateActivity;
use App\Filament\Resources\ActivityResource\Pages\EditActivity;
use Tests\Feature\Filament\Parent\Resource;

class ActivityTest extends Resource
{
    public static string $resourceClass = ActivityResource::class;
    public static string $modelClass = Activity::class;
    public static string $listPage = ListActivities::class;
    public static string $createPage = CreateActivity::class;
    public static string $editPage = EditActivity::class;

    public static array $unsetAttributesBeforeCompare = ['start_date', 'end_date', 'user_id', 'payments'];
}
