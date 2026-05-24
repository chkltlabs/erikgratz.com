<?php

namespace Tests\Unit\Services\Currency;

use App\Enums\CurrencyCode;
use App\Models\Account;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    #[Test]
    public function usd_conversion_uses_multiplier_one(): void
    {
        $service = app(ExchangeRateService::class);

        $this->assertSame(1.0, $service->getToUsdMultiplier(CurrencyCode::USD));
        $this->assertSame(250.0, $service->convertToUsd(250.0, CurrencyCode::USD));
    }

    #[Test]
    public function frankfurter_rates_are_cached_and_applied(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        Account::factory()->create([
            'currency' => CurrencyCode::CAD,
            'balance' => 125,
        ]);

        $service = app(ExchangeRateService::class);
        $service->refreshRatesForAccounts();

        $this->assertEqualsWithDelta(100.0, $service->convertToUsd(125, CurrencyCode::CAD), 0.01);
        $this->assertEqualsWithDelta(100.0, Account::sumBalanceInUsd(), 0.01);
    }

    #[Test]
    public function failover_uses_exchange_rate_api_when_frankfurter_fails(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([], 500),
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'rates' => [
                    'USD' => 1,
                    'CAD' => 2.0,
                ],
            ]),
        ]);

        Account::factory()->create([
            'currency' => CurrencyCode::CAD,
            'balance' => 100,
        ]);

        $service = app(ExchangeRateService::class);
        $service->refreshRatesForAccounts();

        $this->assertEqualsWithDelta(50.0, $service->convertToUsd(100, CurrencyCode::CAD), 0.01);
    }
}
