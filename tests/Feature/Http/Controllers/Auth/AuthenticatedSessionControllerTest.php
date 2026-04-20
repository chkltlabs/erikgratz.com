<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\AuthenticatedSessionController
 */
class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function filament_login_page_returns_ok(): void
    {
        $this->get(route('filament.admin.auth.login'))->assertOk();
    }

    #[Test]
    public function destroy_logs_out_and_redirects_home(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }
}
