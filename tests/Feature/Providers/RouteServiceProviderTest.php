<?php

namespace Tests\Feature\Providers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function home_constant_points_to_admin(): void
    {
        $this->assertSame('/admin', RouteServiceProvider::HOME);
    }

    #[Test]
    public function api_rate_limiter_uses_user_id_when_authenticated(): void
    {
        $this->assertNotNull(RateLimiter::limiter('api'));
        $user = User::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $limiter = RateLimiter::limiter('api');
        $limit = $limiter($request);

        $this->assertStringContainsString((string) $user->id, $this->extractLimiterKey($limit));
    }

    #[Test]
    public function api_rate_limiter_uses_ip_for_guests(): void
    {
        $this->assertNotNull(RateLimiter::limiter('api'));

        $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $request->setUserResolver(fn () => null);

        $limiter = RateLimiter::limiter('api');
        $limit = $limiter($request);

        $this->assertStringContainsString('203.0.113.10', $this->extractLimiterKey($limit));
    }

    private function extractLimiterKey(mixed $limit): string
    {
        $candidate = is_array($limit) ? reset($limit) : $limit;

        $properties = (array) $candidate;

        foreach ($properties as $property => $value) {
            if (str_contains((string) $property, 'key')) {
                return (string) $value;
            }
        }

        return '';
    }
}
