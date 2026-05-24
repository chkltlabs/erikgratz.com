<?php

namespace Tests\Feature\Console;

use App\Enums\CurrencyCode;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefreshExchangeRatesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    #[Test]
    public function command_caches_rates_for_account_currencies(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25, 'EUR' => 0.9],
            ]),
        ]);

        Account::factory()->create(['currency' => CurrencyCode::CAD]);
        Account::factory()->create(['currency' => CurrencyCode::EUR]);

        Artisan::call('fx:refresh');

        $cacheKey = config('currency.cache_key').'.'.now()->toDateString();
        $rates = Cache::get($cacheKey);

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('CAD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEqualsWithDelta(0.8, $rates['CAD'], 0.001);
    }
}
