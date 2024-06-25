<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\InertiaPageController
 */
class InertiaPageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function get_blog_returns_an_ok_response()
    {
        $blogPosts = \App\Models\BlogPost::factory()->times(3)->create();

        $response = $this->get('blog');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_contact_returns_an_ok_response()
    {
        $response = $this->get(route('contact'));

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_donate_returns_an_ok_response()
    {
        $response = $this->get('donate');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_index_returns_an_ok_response()
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_mock_returns_an_ok_response()
    {
        $response = $this->get('mock/{page}');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_play_returns_an_ok_response()
    {
        $response = $this->get('play');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_portfolio_returns_an_ok_response()
    {
        $response = $this->get('portfolio');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_wedding_returns_an_ok_response()
    {
        $response = $this->get('wedding');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    // test cases...
}
