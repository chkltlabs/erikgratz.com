<?php

namespace Tests\Feature\Models;

use App\Enums\Period;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function one_time_due_scopes_filter_by_month_and_year(): void
    {
        Carbon::setTestNow('2026-08-20');

        $spend = Spend::factory()->bare()->noPayments()->create();

        $thisMonth = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'paid_on' => '2026-08-10',
            'is_paid' => true,
            'card_id' => null,
        ]);
        $nextMonth = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'paid_on' => '2026-09-05',
            'is_paid' => false,
            'card_id' => null,
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $spend->id,
            'paid_on' => '2025-08-10',
            'is_paid' => false,
            'card_id' => null,
        ]);

        $this->assertTrue(Payment::oneTimeDueThisMonth()->whereKey($thisMonth)->exists());
        $this->assertTrue(Payment::oneTimeDueNextMonth()->whereKey($nextMonth)->exists());
        $this->assertFalse(Payment::oneTimeUnpaidDueThisMonth()->whereKey($thisMonth)->exists());
        $this->assertTrue(Payment::oneTimeUnpaidDueNextMonth()->whereKey($nextMonth)->exists());
        $this->assertTrue(Payment::oneTimeUnpaid()->whereKey($nextMonth)->exists());
        $this->assertFalse(Payment::oneTimeUnpaid()->whereKey($thisMonth)->exists());
    }

    #[Test]
    public function monthly_scopes_require_paid_on_and_respect_day_for_unpaid(): void
    {
        Carbon::setTestNow('2026-08-20');

        $monthly = PeriodicSpend::factory()->create(['period' => Period::Monthly]);

        $futureDay = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $monthly->id,
            'paid_on' => now()->setDay(25),
            'is_paid' => false,
            'card_id' => null,
        ]);
        $pastDay = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $monthly->id,
            'paid_on' => now()->setDay(5),
            'is_paid' => false,
            'card_id' => null,
        ]);
        $nullPaidOn = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $monthly->id,
            'paid_on' => null,
            'is_paid' => false,
            'card_id' => null,
        ]);

        $this->assertTrue(Payment::monthly()->whereKey($futureDay)->exists());
        $this->assertTrue(Payment::monthlyUnpaid()->whereKey($futureDay)->exists());
        $this->assertFalse(Payment::monthlyUnpaid()->whereKey($pastDay)->exists());
        $this->assertFalse(Payment::monthlyUnpaid()->whereKey($nullPaidOn)->exists());
        $this->assertTrue(Payment::monthlyAllUnpaid()->whereKey($pastDay)->exists());
        $this->assertFalse(Payment::monthlyAllUnpaid()->whereKey($nullPaidOn)->exists());
    }

    #[Test]
    public function yearly_scopes_filter_by_paid_on_and_exclude_nulls(): void
    {
        Carbon::setTestNow('2026-08-20');

        $yearly = PeriodicSpend::factory()->create(['period' => Period::Yearly]);

        $thisMonthFutureDay = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $yearly->id,
            'paid_on' => '2026-08-25',
            'is_paid' => false,
            'card_id' => null,
        ]);
        $nextMonth = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $yearly->id,
            'paid_on' => '2026-09-10',
            'is_paid' => false,
            'card_id' => null,
        ]);
        $laterMonth = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $yearly->id,
            'paid_on' => '2026-11-25',
            'is_paid' => false,
            'card_id' => null,
        ]);
        $nullPaidOn = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $yearly->id,
            'paid_on' => null,
            'is_paid' => false,
            'card_id' => null,
        ]);

        $this->assertTrue(Payment::yearlyDueThisMonth()->whereKey($thisMonthFutureDay)->exists());
        $this->assertTrue(Payment::yearlyDueNextMonth()->whereKey($nextMonth)->exists());
        $this->assertTrue(Payment::yearlyUnpaidDueThisMonth()->whereKey($thisMonthFutureDay)->exists());
        $this->assertTrue(Payment::yearlyUnpaid()->whereKey($thisMonthFutureDay)->exists());
        $this->assertTrue(Payment::yearlyUnpaid()->whereKey($laterMonth)->exists());
        $this->assertTrue(Payment::yearlyUnpaidAll()->whereKey($nextMonth)->exists());
        $this->assertFalse(Payment::yearlyUnpaidAll()->whereKey($nullPaidOn)->exists());
        $this->assertFalse(Payment::yearlyDueNextMonth()->whereKey($nullPaidOn)->exists());
    }

    #[Test]
    public function cashflow_due_with_loaded_card_uses_statement_cycle(): void
    {
        Carbon::setTestNow('2026-08-20');

        $card = \App\Models\Card::factory()->create([
            'statement_date' => 15,
            'due_date' => 5,
        ]);
        $payment = Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => Spend::factory()->bare()->noPayments()->create()->id,
            'paid_on' => '2026-08-10',
            'is_paid' => false,
            'card_id' => $card->id,
        ]);
        $payment->load('card');

        $due = $payment->cashflowDueDate();

        $this->assertNotNull($due);
        $this->assertSame('2026-09-05', $due->toDateString());
        $this->assertTrue($payment->cashflowDueFallsInMonth(Carbon::parse('2026-09-01')));
        $this->assertFalse($payment->cashflowDueFallsInMonth(Carbon::parse('2026-08-01')));
    }
}
