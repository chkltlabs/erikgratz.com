<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\EmailVerificationPromptController
 */
class EmailVerificationPromptControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unverified_user_sees_verification_prompt(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }

    #[Test]
    public function verified_user_is_redirected_home(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(RouteServiceProvider::HOME);
    }
}
