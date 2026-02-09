<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Photos\PhotoResource;
use App\Models\Photo;
use App\Filament\Resources\Photos\Pages\ListPhotos;
use App\Filament\Resources\Photos\Pages\CreatePhoto;
use App\Filament\Resources\Photos\Pages\EditPhoto;
use Tests\Feature\Filament\Parent\Resource;

class PhotoTest extends Resource
{
    public static string $resourceClass = PhotoResource::class;
    public static string $modelClass = Photo::class;
    public static string $listPage = ListPhotos::class;
    public static string $createPage = CreatePhoto::class;
    public static string $editPage = EditPhoto::class;

    public static array $unsetAttributesBeforeCompare = ['title', 'path', 'url', 'description', 'tags'];
}
