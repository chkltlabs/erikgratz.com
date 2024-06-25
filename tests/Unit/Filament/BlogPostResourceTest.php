<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\BlogPostResource;
use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\EditBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\ListBlogPosts;
use App\Models\BlogPost;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BlogPostResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::first() ?? User::factory()->create());
    }

    public function test_blog_post_list_renders()
    {
        // $this->get(BlogPostResource::getUrl('index'))->assertSuccessful();
        Livewire::test(ListBlogPosts::class)->assertHasNoErrors();
    }

    public function test_blog_post_list_includes_entries()
    {
        BlogPost::query()->delete();
        $posts = BlogPost::factory()->times(2)->create();
        Livewire::test(ListBlogPosts::class)
            ->assertCanSeeTableRecords($posts);
    }

    public function test_blog_post_create_renders()
    {
        // $this->get(BlogPostResource::getUrl('create'))->assertSuccessful();
        Livewire::test(CreateBlogPost::class)->assertHasNoErrors();
    }

    public function test_blog_post_can_create()
    {
        $post = BlogPost::factory()->make();
        Livewire::test(CreateBlogPost::class)
            ->fillForm($post->toArray())
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas($post->getTable(),
            array_intersect_key(
                $post->toArray(),
                array_flip(['title', 'subtitle', 'body'])
            ));
    }

    public function test_blog_post_edit_renders()
    {
        $post = BlogPost::factory()->create();
        // $this->get(BlogPostResource::getUrl('edit', [
        //     'record' => $post->id
        // ]))->assertSuccessful();
        Livewire::test(EditBlogPost::class, [
            'record' => $post->id,
        ])->assertHasNoErrors();
    }

    public function test_blog_post_can_edit()
    {
        $original = BlogPost::factory()->create();
        $post = BlogPost::factory()->make();
        Livewire::test(EditBlogPost::class, [
            'record' => $original->id,
        ])
            ->fillForm($post->toArray())
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas($post->getTable(),
            array_intersect_key(
                $post->toArray(),
                array_flip(['title', 'subtitle', 'body'])
            ));
    }

    public function test_blog_post_can_delete()
    {
        $post = BlogPost::factory()->create();
        Livewire::test(EditBlogPost::class, [
            'record' => $post->id,
        ])
            ->call('delete')
            ->assertHasNoFormErrors();
        $this->assertDatabaseMissing($post->getTable(), $post->toArray());
    }
}
