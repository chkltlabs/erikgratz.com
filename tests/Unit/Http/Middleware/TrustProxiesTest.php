<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\TrustProxies;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setTrustedProxiesEnv(null);

        parent::tearDown();
    }

    #[Test]
    public function empty_env_trusts_no_proxies(): void
    {
        $this->setTrustedProxiesEnv(null);

        $this->assertSame([], $this->proxies(new TrustProxies));
    }

    #[Test]
    public function star_trusts_all_proxies(): void
    {
        $this->setTrustedProxiesEnv(' * ');

        $this->assertSame('*', $this->proxies(new TrustProxies));
    }

    #[Test]
    public function comma_list_is_trimmed(): void
    {
        $this->setTrustedProxiesEnv(' 10.0.0.1 , 10.0.0.2, ');

        $this->assertSame(['10.0.0.1', '10.0.0.2'], $this->proxies(new TrustProxies));
    }

    private function setTrustedProxiesEnv(?string $value): void
    {
        if ($value === null) {
            putenv('TRUSTED_PROXIES');
            unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);

            return;
        }

        putenv('TRUSTED_PROXIES='.$value);
        $_ENV['TRUSTED_PROXIES'] = $value;
        $_SERVER['TRUSTED_PROXIES'] = $value;
    }

    /**
     * @return array<int, string>|string|null
     */
    private function proxies(TrustProxies $middleware): array|string|null
    {
        $property = new ReflectionProperty(TrustProxies::class, 'proxies');

        return $property->getValue($middleware);
    }
}
