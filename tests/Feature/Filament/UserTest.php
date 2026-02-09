<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use Tests\Feature\Filament\Parent\Resource;

class UserTest extends Resource
{
    public static string $resourceClass = UserResource::class;
    public static string $modelClass = User::class;
    public static string $listPage = ListUsers::class;
    public static string $createPage = CreateUser::class;
    public static string $editPage = EditUser::class;

    public static array $unsetAttributesBeforeCompare = ['password', 'email_verified_at'];
}
