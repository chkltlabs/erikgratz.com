<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Middleware\RedirectIfAuthenticated
 */
class RedirectIfAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_request_passes_through_middleware(): void
    {
        $middleware = new RedirectIfAuthenticated;
        $request = Request::create('/login', 'GET');

        $response = $middleware->handle($request, fn () => response('next'));

        $this->assertSame('next', $response->getContent());
    }

    #[Test]
    public function authenticated_request_redirects_to_filament_dashboard(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $middleware = new RedirectIfAuthenticated;
        $request = Request::create('/login', 'GET');

        $response = $middleware->handle($request, fn () => response('next'));

        $this->assertTrue($response->isRedirect(route('filament.admin.pages.dashboard')));
    }

    #[Test]
    public function authenticated_request_redirects_when_explicit_guard_is_used(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $middleware = new RedirectIfAuthenticated;
        $request = Request::create('/login', 'GET');

        $response = $middleware->handle($request, fn () => response('next'), 'web');

        $this->assertTrue($response->isRedirect(route('filament.admin.pages.dashboard')));
    }
}
