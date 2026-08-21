<?php

namespace Tests\Unit\Models;

use App\Models\Card;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCashflowDueDateTest extends TestCase
{
    #[Test]
    public function null_paid_on_has_no_cashflow_due_date(): void
    {
        $payment = Payment::factory()->make([
            'card_id' => null,
            'paid_on' => null,
        ]);
        $payment->setRelation('card', null);

        $this->assertNull($payment->cashflowDueDate());
        $this->assertFalse($payment->cashflowDueFallsInMonth(now()->addMonth()->startOfMonth()));
    }

    #[Test]
    public function null_card_floats_one_month_from_paid_on(): void
    {
        Carbon::setTestNow('2026-05-15');

        $payment = Payment::factory()->make([
            'card_id' => null,
            'paid_on' => '2026-05-10',
        ]);
        $payment->setRelation('card', null);

        $due = $payment->cashflowDueDate();

        $this->assertSame('2026-06-10', $due->toDateString());
    }

    #[Test]
    public function spend_before_statement_close_is_due_on_following_due_date(): void
    {
        Carbon::setTestNow('2026-05-15');

        $card = Card::factory()->make([
            'statement_date' => 15,
            'due_date' => 5,
        ]);

        // Spend May 10 → closes May 15 → due June 5
        $due = $card->cashflowDueDateForSpendDate(Carbon::parse('2026-05-10'));

        $this->assertSame('2026-06-05', $due->toDateString());
    }

    #[Test]
    public function spend_after_statement_close_rolls_to_next_statement(): void
    {
        Carbon::setTestNow('2026-05-15');

        $card = Card::factory()->make([
            'statement_date' => 15,
            'due_date' => 5,
        ]);

        // Spend May 20 → closes June 15 → due July 5
        $due = $card->cashflowDueDateForSpendDate(Carbon::parse('2026-05-20'));

        $this->assertSame('2026-07-05', $due->toDateString());
    }

    #[Test]
    public function due_after_close_in_same_month_when_due_day_is_after_statement_day(): void
    {
        $card = Card::factory()->make([
            'statement_date' => 10,
            'due_date' => 25,
        ]);

        // Spend May 5 → closes May 10 → due May 25
        $due = $card->cashflowDueDateForSpendDate(Carbon::parse('2026-05-05'));

        $this->assertSame('2026-05-25', $due->toDateString());
    }
}
