<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\NewPasswordController
 */
class NewPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function create_renders_reset_password_page_with_token_and_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = Str::random(64);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee($user->email, false);
    }

    #[Test]
    public function store_resets_password_and_redirects_to_login(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
                ->assertRedirect(route('filament.admin.auth.login'))
                ->assertSessionHas('status');

            return true;
        });

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    #[Test]
    public function store_returns_validation_error_when_reset_fails(): void
    {
        $user = User::factory()->create();

        $this->from('/forgot-password')
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }
}
