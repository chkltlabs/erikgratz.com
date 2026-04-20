<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\PasswordResetLinkController
 */
class PasswordResetLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function create_renders_forgot_password_page(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    #[Test]
    public function store_sends_reset_link_for_known_user(): void
    {
        Notification::fake();
        /** @var User $user */
        $user = User::factory()->create();

        $this->from('/forgot-password')
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function store_returns_validation_error_for_unknown_user(): void
    {
        $this->from('/forgot-password')
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }
}
