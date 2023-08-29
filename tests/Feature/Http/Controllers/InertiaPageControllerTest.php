<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

// uses(RefreshDatabase::class);

/**
 * @see \App\Http\Controllers\InertiaPageController
 */

test('get blog returns an ok response', function () {
    $blogPosts = \App\Models\BlogPost::factory()->times(3)->create();

    $response = $this->get('blog');

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get contact returns an ok response', function () {
    $response = $this->get(route('contact'));

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get donate returns an ok response', function () {
    $response = $this->get('donate');

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get index returns an ok response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get mock returns an ok response', function () {
    $response = $this->get('mock/{page}');

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get play returns an ok response', function () {
    $response = $this->get('play');

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get portfolio returns an ok response', function () {
    $response = $this->get('portfolio');

    $response->assertOk();

    // TODO: perform additional assertions
});

test('get wedding returns an ok response', function () {
    $response = $this->get('wedding');

    $response->assertOk();

    // TODO: perform additional assertions
});
