<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


/**
 * @see \App\Http\Controllers\Auth\AuthenticatedSessionController
 */

test('create returns an ok response', function () {
    $response = $this->get(route('login'));

    $response->assertOk();

    // TODO: perform additional assertions
});

test('destroy returns an ok response', function () {

    $response = $this->post(route('logout'), [
        // TODO: send request data
    ]);

    $response->assertRedirect('/login');

    // TODO: perform additional assertions
});

test('store returns an ok response', function () {
    $response = $this->post('login', [
        // TODO: send request data
    ]);
    
    $response->assertRedirect();

    // TODO: perform additional assertions
});

test('store validates with a form request', function () {
    $this->assertActionUsesFormRequest(
        \App\Http\Controllers\Auth\AuthenticatedSessionController::class,
        'store',
        \App\Http\Requests\Auth\LoginRequest::class
    );
});
