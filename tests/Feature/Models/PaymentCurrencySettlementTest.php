<?php

namespace Tests\Feature\Models;

use App\Enums\CurrencyCode;
use App\Models\Payment;
use App\Models\Spend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCurrencySettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function marking_foreign_payment_paid_converts_amount_using_paid_on_date_rate(): void
    {
        Http::fake([
            'api.frankfurter.dev/v1/2024-06-01*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => '2024-06-01',
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        $payment = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => Spend::factory()->bare()->noPayments()->create()->id,
            'currency' => CurrencyCode::CAD,
            'amount' => 125,
            'is_paid' => false,
            'paid_on' => '2024-06-01',
        ]);

        $payment->update(['is_paid' => true]);

        $payment->refresh();

        $this->assertSame(CurrencyCode::USD, $payment->currency);
        $this->assertEqualsWithDelta(100.0, (float) $payment->amount, 0.01);
    }

    #[Test]
    public function payments_due_today_settle_when_marked_paid_like_daily_upkeep(): void
    {
        Carbon::setTestNow('2024-06-01');

        Http::fake([
            'api.frankfurter.dev/v1/2024-06-01*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => '2024-06-01',
                'rates' => ['EUR' => 0.8],
            ]),
        ]);

        $payment = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => Spend::factory()->bare()->noPayments()->create()->id,
            'currency' => CurrencyCode::EUR,
            'amount' => 80,
            'is_paid' => false,
            'paid_on' => '2024-06-01',
        ]);

        Payment::query()
            ->where('paid_on', '=', now()->toDateString())
            ->where('is_paid', false)
            ->each(fn (Payment $due) => $due->update(['is_paid' => true]));

        $payment->refresh();

        $this->assertTrue($payment->is_paid);
        $this->assertSame(CurrencyCode::USD, $payment->currency);
        $this->assertEqualsWithDelta(100.0, (float) $payment->amount, 0.01);
    }

    #[Test]
    public function unpaid_foreign_payment_amount_in_usd_uses_latest_rate(): void
    {
        Http::fake([
            'api.frankfurter.dev/*' => Http::response([
                'amount' => 1,
                'base' => 'USD',
                'date' => now()->toDateString(),
                'rates' => ['CAD' => 1.25],
            ]),
        ]);

        $payment = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => Spend::factory()->bare()->noPayments()->create()->id,
            'currency' => CurrencyCode::CAD,
            'amount' => 125,
            'is_paid' => false,
        ]);

        app(\App\Services\Currency\ExchangeRateService::class)->ensureRatesForDate(
            now()->toDateString(),
            ['CAD'],
        );

        $this->assertEqualsWithDelta(100.0, $payment->amountInUsd(), 0.01);
    }
}
