<?php

namespace Tests\Feature\Models;

use App\Enums\CurrencyCode;
use App\Models\Account;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\StateDump;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StateDumpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dump_stores_data_for_configured_models(): void
    {
        // Setup some data
        User::factory()->create();
        SimpleFinAccount::factory()->create(['id' => 'test-acc']);

        $dump = StateDump::dump();

        $this->assertNotNull($dump);
        $this->assertIsArray($dump->data);
        $this->assertArrayHasKey(SimpleFinAccount::class, $dump->data);
        $this->assertNotEmpty($dump->data[SimpleFinAccount::class]);
    }

    public function test_cache_flags_trigger_dump(): void
    {
        Cache::forget(StateDump::SHOULD_DUMP);

        StateDump::setShouldDumpFlag();
        $this->assertTrue(Cache::has(StateDump::SHOULD_DUMP));

        $countBefore = StateDump::count();
        StateDump::checkShouldDump();

        $this->assertFalse(Cache::has(StateDump::SHOULD_DUMP));
        $this->assertEquals($countBefore + 1, StateDump::count());
    }

    public function test_get_stat_for_model_returns_correct_value(): void
    {
        $acc = SimpleFinAccount::factory()->create(['id' => 'stat-test', 'balance' => 123.45]);
        $dump = StateDump::dump();

        $val = $dump->getStatForModel($acc, 'balance');
        $this->assertEquals(123.45, (float) $val);

        // Test missing model in dump
        $acc2 = new SimpleFinAccount(['id' => 'not-in-dump']);
        $this->assertEquals(0, $dump->getStatForModel($acc2, 'balance'));
    }

    public function test_dump_includes_exchange_rates_for_accounts(): void
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

        $dump = StateDump::dump();

        $this->assertArrayHasKey('exchange_rates', $dump->data);
        $this->assertEqualsWithDelta(0.8, $dump->data['exchange_rates']['multipliers']['CAD'], 0.001);
    }

    public function test_account_balance_in_dump_is_converted_to_usd(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        $account = Account::factory()->create([
            'currency' => CurrencyCode::CAD,
            'balance' => 125,
        ]);

        $dump = StateDump::dump();

        $this->assertEqualsWithDelta(100.0, (float) $dump->getStatForModel($account, 'balance'), 0.01);
    }

    public function test_legacy_dump_without_exchange_rates_treats_balance_as_usd(): void
    {
        $account = Account::factory()->create([
            'currency' => CurrencyCode::CAD,
            'balance' => 200,
        ]);

        $dump = StateDump::create([
            'data' => [
                Account::class => [
                    [
                        'id' => $account->id,
                        'balance' => 200,
                        'currency' => 'CAD',
                    ],
                ],
            ],
        ]);

        $this->assertEquals(200.0, (float) $dump->getStatForModel($account, 'balance'));
    }
}
