<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $constr = ['email_verified_at' => null];
    $this->user = 
        User::where($constr)->first() 
        ?? User::factory()->create($constr);
    $this->actingAs($this->user);
});
test('email verification screen can be rendered', function () {
    $this->get('/verify-email')
    ->assertStatus(200);
});

test('email can be verified', function () {
    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $this->user->id, 
            'hash' => sha1($this->user->email)
        ]
    );

    $this->get($verificationUrl)
    ->assertRedirect(
        RouteServiceProvider::HOME.'?verified=1'
    );

    Event::assertDispatched(Verified::class);
    expect($this->user->fresh()->hasVerifiedEmail())
    ->toBeTrue();
    
});

test('email is not verified with invalid hash', function () {

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $this->user->id, 
            'hash' => sha1('wrong-email')
        ]
    );

    $this->get($verificationUrl);

    expect($this->user->fresh()->hasVerifiedEmail())
    ->toBeFalse();
});
