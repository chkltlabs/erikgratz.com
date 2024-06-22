<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\BlogPost;
use App\Models\User;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\InertiaDashboardController
 */
class InertiaDashboardControllerTest extends TestCase
{
    /**
     * @test
     */
    public function get_blog_edit_returns_an_ok_response()
    {
        $user = User::whereHas('blogPosts')->first() ?? User::factory()->has(BlogPost::factory()->count(3))->create();
        $this->actingAs($user);
        $blogPost = $user->blogPosts->random();
        $response = $this->get('blog/edit/'.$blogPost->id);

        $response->assertOk();

    }

    /**
     * @test
     */
    public function get_blog_listing_returns_an_ok_response()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('posts'));

        $response->assertOk();

    }

    /**
     * @test
     */
    public function get_blog_new_returns_an_ok_response()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        $response = $this->get('blog/new');

        $response->assertOk();
    }

    /**
     * @test
     */
    public function get_dashboard_returns_an_ok_response()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('dashboard'));

        // $response->assertOk();
        // 2023-06-24 : Filament replaces breeze dashboard
        $response->assertRedirectToRoute('filament.admin.pages.dashboard');

    }

    /**
     * @test
     */
    public function post_blog_edit_returns_an_ok_response()
    {
        $user = User::whereHas('blogPosts')->first() ?? User::factory()->has(BlogPost::factory()->count(3))->create();
        $this->actingAs($user);
        $blogPost = $user->blogPosts->random();
        $response = $this->post('blog/edit/'.$blogPost->id, [
            'id' => $blogPost->id,
            'title' => 'splat',
            'subtitle' => 'spladily',
            'body' => 'The quick brown fox jumps over the lazy dog.',
        ]);

        $response->assertRedirect('/blog/listing');
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'splat',
            'subtitle' => 'spladily',
            'body' => 'The quick brown fox jumps over the lazy dog.',
        ]);

    }

    /**
     * @test
     */
    public function post_blog_new_returns_an_ok_response()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        $response = $this->post('blog/new', [
            'user_id' => $user->id,
            'title' => 'spoot',
            'subtitle' => 'spoodily',
            'body' => 'The quick brown fox jumps over the lazy dog.',
            'is_public' => false,
            'tags' => ['foo', 'bar'],
        ]);
        $response->assertRedirect('/blog/listing');
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'spoot',
            'subtitle' => 'spoodily',
            'body' => 'The quick brown fox jumps over the lazy dog.',
        ]);
    }

    // test cases...
}
