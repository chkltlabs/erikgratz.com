<?php

namespace Tests\Feature\Filament;

use App\Enums\CurrencyCode;
use App\Filament\Widgets\AccountWidget;
use App\Models\Account;
use App\Models\User;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function it_saves_resolved_decimal_expression_for_balance(): void
    {
        $account = Account::factory()->create(['balance' => 1500]);

        Livewire::test(AccountWidget::class)
            ->call('updateTableColumnState', 'balance', (string) $account->getKey(), '1500 - 200');

        $this->assertSame(1300.0, (float) $account->fresh()->balance);
    }

    #[Test]
    public function it_rejects_invalid_expression_without_updating_balance(): void
    {
        $account = Account::factory()->create(['balance' => 1500]);

        $response = Livewire::test(AccountWidget::class)
            ->call('updateTableColumnState', 'balance', (string) $account->getKey(), '1500 - abc');

        $response->assertReturned(fn (mixed $returned): bool => is_array($returned)
            && array_key_exists('error', $returned)
            && str_contains($returned['error'], 'valid number or math expression'));
        $this->assertSame(1500.0, (float) $account->fresh()->balance);
    }

    #[Test]
    public function it_renders_widget_table_with_mixed_currency_accounts(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        Account::factory()->create(['currency' => CurrencyCode::CAD, 'balance' => 125]);

        app(ExchangeRateService::class)->refreshRatesForAccounts();

        Livewire::test(AccountWidget::class)->assertSuccessful();
    }

    #[Test]
    public function name_returns_display_name_when_set(): void
    {
        $account = Account::factory()->create(['display_name' => 'My Savings']);

        $this->assertSame('My Savings', $account->name);
    }

    #[Test]
    public function name_falls_back_to_user_type_when_display_name_is_blank(): void
    {
        $account = Account::factory()->create(['display_name' => '']);

        $this->assertSame($account->user->name.' '.$account->type, $account->name);
    }

    #[Test]
    public function name_falls_back_to_user_type_when_display_name_is_null(): void
    {
        $account = Account::factory()->create(['display_name' => null]);

        $this->assertSame($account->user->name.' '.$account->type, $account->name);
    }

    #[Test]
    public function sum_balance_in_usd_converts_mixed_currencies(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        Account::factory()->create(['currency' => CurrencyCode::USD, 'balance' => 100]);
        Account::factory()->create(['currency' => CurrencyCode::CAD, 'balance' => 125]);

        app(ExchangeRateService::class)->refreshRatesForAccounts();

        $this->assertEqualsWithDelta(200.0, Account::sumBalanceInUsd(), 0.01);
    }
}
