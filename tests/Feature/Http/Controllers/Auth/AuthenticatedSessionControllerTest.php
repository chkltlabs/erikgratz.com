<?php

namespace Tests\Feature\Http\Controllers\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\AuthenticatedSessionController
 */
class AuthenticatedSessionControllerTest extends TestCase
{
    #[Test]
    public function create_returns_an_ok_response()
    {
        $response = $this->get(route('filament.admin.auth.login'));

        $response->assertOk();

        // TODO: perform additional assertions
    }

    #[Test]
    public function destroy_returns_an_ok_response()
    {

        $response = $this->post(route('logout'), [
            // TODO: send request data
        ]);

        $response->assertRedirect('/admin/login');

        // TODO: perform additional assertions
    }

    #[Test]
    public function store_returns_an_ok_response()
    {
        $response = $this->post('login', [
            // TODO: send request data
        ]);

        $response->assertRedirect();

        // TODO: perform additional assertions
    }

    #[Test]
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Auth\AuthenticatedSessionController::class,
            'store',
            \App\Http\Requests\Auth\LoginRequest::class
        );
    }

    // test cases...
}
