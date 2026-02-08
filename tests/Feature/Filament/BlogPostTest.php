<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Filament\Resources\BlogPostResource\Pages\ListBlogPosts;
use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\EditBlogPost;
use Tests\Feature\Filament\Parent\Resource;

class BlogPostTest extends Resource
{
    public static string $resourceClass = BlogPostResource::class;
    public static string $modelClass = BlogPost::class;
    public static string $listPage = ListBlogPosts::class;
    public static string $createPage = CreateBlogPost::class;
    public static string $editPage = EditBlogPost::class;

    public static array $unsetAttributesBeforeCompare = ['user_id', 'posted', 'edited'];
}
