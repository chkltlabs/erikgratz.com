<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    $this->actingAs(User::first() ?? User::factory()->create());
});
test('confirm password screen can be rendered', function () {
    $this->get('/confirm-password')
    ->assertStatus(200);
});

test('password can be confirmed', function () {
    $this->post('/confirm-password', [
        'password' => 'password',
    ])
    ->assertRedirect()
    ->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $this->post('/confirm-password', [
        'password' => 'wrong-password',
    ])
    ->assertSessionHasErrors();
});
