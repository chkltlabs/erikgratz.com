<?php

namespace Tests\Feature\Filament;

use App\Enums\CurrencyCode;
use App\Filament\Widgets\NetWorthStats;
use App\Models\Account;
use App\Models\Card;
use App\Models\User;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NetWorthStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function it_renders_stats_with_usd_normalized_account_balance(): void
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
        Card::factory()->create(['balance' => 10, 'pending' => 5]);

        app(ExchangeRateService::class)->refreshRatesForAccounts();

        Livewire::test(NetWorthStats::class)
            ->assertSuccessful()
            ->assertSee('Bank Balance')
            ->assertSee('CC Balance');
    }
}
