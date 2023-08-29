<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('registration screen can be rendered', function () {
    $this->get('/register')->assertStatus(200);
});

test('new users can register', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => rand(0,999).'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
    ->assertRedirect(RouteServiceProvider::HOME);
    $this->assertAuthenticated();
});
