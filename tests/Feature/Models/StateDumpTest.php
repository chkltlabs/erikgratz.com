<?php

namespace Tests\Feature\Models;

use App\Enums\CurrencyCode;
use App\Models\Account;
use App\Models\BenefitUsage;
use App\Models\CardBenefit;
use App\Models\LoyaltyMembership;
use App\Models\PointRedemption;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\StateDump;
use App\Models\User;
use App\Services\Dashboard\DumpChartData;
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

    public function test_dump_stores_loyalty_membership_points(): void
    {
        $membership = LoyaltyMembership::factory()->create(['points_balance' => 25000]);

        $dump = StateDump::dump();

        $this->assertArrayHasKey(LoyaltyMembership::class, $dump->data);

        $row = collect($dump->data[LoyaltyMembership::class])->firstWhere('id', $membership->id);

        $this->assertNotNull($row);
        $this->assertSame(25000, $row['points_balance']);
    }

    public function test_dump_stores_benefit_usage_with_card_and_captured_value(): void
    {
        $benefit = CardBenefit::factory()->create(['value' => 300]);
        $usage = BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'amount' => 80,
        ]);

        $dump = StateDump::dump();

        $this->assertArrayHasKey(BenefitUsage::class, $dump->data);
        $row = collect($dump->data[BenefitUsage::class])->firstWhere('id', $usage->id);

        $this->assertNotNull($row);
        $this->assertSame($benefit->card_id, $row['card_id']);
        $this->assertEquals(80.0, $row['captured']);
        $this->assertArrayNotHasKey('benefit', $row);
    }

    public function test_dump_stores_point_redemptions(): void
    {
        $redemption = PointRedemption::factory()->create([
            'points_spent' => 50000,
            'money_spent' => 100,
            'cash_value' => 750,
        ]);

        $dump = StateDump::dump();

        $this->assertArrayHasKey(PointRedemption::class, $dump->data);
        $row = collect($dump->data[PointRedemption::class])->firstWhere('id', $redemption->id);

        $this->assertNotNull($row);
        $this->assertEquals(50000, $row['points_spent']);
        $this->assertEquals(100.0, (float) $row['money_spent']);
        $this->assertEquals(750.0, (float) $row['cash_value']);
    }

    public function test_dump_forgets_chart_caches(): void
    {
        Cache::put(DumpChartData::PAST_STATS_CACHE, ['stale'], now()->endOfDay());
        Cache::put(DumpChartData::BENEFIT_USAGE_CACHE, ['stale'], now()->endOfDay());
        Cache::put(DumpChartData::REDEMPTIONS_CACHE, ['stale'], now()->endOfDay());

        StateDump::dump();

        $this->assertFalse(Cache::has(DumpChartData::PAST_STATS_CACHE));
        $this->assertFalse(Cache::has(DumpChartData::BENEFIT_USAGE_CACHE));
        $this->assertFalse(Cache::has(DumpChartData::REDEMPTIONS_CACHE));
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
