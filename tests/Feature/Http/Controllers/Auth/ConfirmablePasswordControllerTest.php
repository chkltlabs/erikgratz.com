<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ConfirmablePasswordController
 */
class ConfirmablePasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function show_renders_for_authenticated_user(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk();
    }

    #[Test]
    public function store_confirms_password_and_redirects_home(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user)
            ->post('/confirm-password', [
                'password' => 'password123',
            ])
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    #[Test]
    public function store_rejects_invalid_password_with_error(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $this->actingAs($user)
            ->from('/confirm-password')
            ->post('/confirm-password', [
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/confirm-password')
            ->assertSessionHasErrors('password');
    }
}
