<?php

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @see \App\Http\Controllers\InertiaDashboardController
 */

test('get blog edit returns an ok response', function () {
    $user = User::whereHas('blogPosts')->first() ?? User::factory()->has(BlogPost::factory()->count(3))->create();
    $this->actingAs($user);
    $blogPost = $user->blogPosts->random();
    $response = $this->get('blog/edit/'.$blogPost->id);

    $response->assertOk();

});

test('get blog listing returns an ok response', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('posts'));

    $response->assertOk();

});

test('get blog new returns an ok response', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user);
    $response = $this->get('blog/new');

    $response->assertOk();
});

test('get dashboard returns an ok response', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user);
    $response = $this->get(route('dashboard'));

    // $response->assertOk();
    // 2023-06-24 : Filament replaces breeze dashboard
    $response->assertRedirectToRoute('filament.pages.dashboard');

});

test('post blog edit returns an ok response', function ()
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

});

test('post blog new returns an ok response', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user);
    $response = $this->post('blog/new', [
        'user_id' => $user->id,
        'title' => 'spoot',
        'subtitle' => 'spoodily',
        'body' => 'The quick brown fox jumps over the lazy dog.',
        'is_public' => false,
        'tags' => ['foo','bar'],
    ]);
    $response->assertRedirect('/blog/listing');
    $this->assertDatabaseHas('blog_posts', [
        'title' => 'spoot',
        'subtitle' => 'spoodily',
        'body' => 'The quick brown fox jumps over the lazy dog.',
    ]);
});
