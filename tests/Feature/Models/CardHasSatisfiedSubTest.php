<?php

namespace Tests\Feature\Models;

use App\Models\Card;
use App\Models\Payment;
use App\Models\Spend;
use App\Models\StateDump;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardHasSatisfiedSubTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();

        parent::tearDown();
    }

    #[Test]
    public function current_outstanding_alone_can_satisfy_the_sub(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 3900, pending: 100);

        $this->assertSame(4000.0, $card->subSpendProgress());
        $this->assertTrue($card->has_satisfied_sub);
    }

    #[Test]
    public function dump_history_counts_preceding_isb_when_it_drops_to_zero(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 100, pending: 0);

        $this->dumpCardAt($card, '2026-04-20 23:50:00', isb: 2000, balance: 9999);
        $this->dumpCardAt($card, '2026-05-10 23:50:00', isb: 1500, balance: 50);
        $this->dumpCardAt($card, '2026-05-20 23:50:00', isb: 0, balance: 50);

        $card->refresh();

        $this->assertSame(1600.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function isb_that_never_drops_to_zero_is_not_counted_as_a_payment(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 250, pending: 0);

        $this->dumpCardAt($card, '2026-04-20 23:50:00', isb: 2000);
        $this->dumpCardAt($card, '2026-05-10 23:50:00', isb: 2500);
        $this->dumpCardAt($card, '2026-06-01 23:50:00', isb: 1800);

        $card->refresh();

        $this->assertSame(250.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function two_isb_payoffs_are_summed(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 0, pending: 0);

        $this->dumpCardAt($card, '2026-04-20 23:50:00', isb: 2000);
        $this->dumpCardAt($card, '2026-05-01 23:50:00', isb: 0);
        $this->dumpCardAt($card, '2026-05-20 23:50:00', isb: 2200);
        $this->dumpCardAt($card, '2026-06-01 23:50:00', isb: 0);

        $card->refresh();

        $this->assertSame(4200.0, $card->subSpendProgress());
        $this->assertTrue($card->has_satisfied_sub);
    }

    #[Test]
    public function paid_off_dump_history_satisfies_even_when_current_and_tracked_purchases_do_not(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 200, pending: 0);

        $this->dumpCardAt($card, '2026-04-20 23:50:00', 2500);
        $this->dumpCardAt($card, '2026-05-20 23:50:00', 4100);
        $this->dumpCardAt($card, '2026-06-01 23:50:00', 0);

        $this->trackedPaidPurchase($card, 300);

        $card->refresh();

        $this->assertSame(4300.0, $card->subSpendProgress());
        $this->assertTrue($card->has_satisfied_sub);
    }

    #[Test]
    public function current_balance_since_last_payoff_counts_toward_the_sub(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 1500, pending: 100);

        $this->dumpCardAt($card, '2026-05-01 23:50:00', 3000);
        $this->dumpCardAt($card, '2026-05-20 23:50:00', 0);

        $card->refresh();

        $this->assertSame(4600.0, $card->subSpendProgress());
        $this->assertTrue($card->has_satisfied_sub);
    }

    #[Test]
    public function planned_charges_inside_the_window_count_and_outside_do_not(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 500, pending: 0);

        $this->dumpCardAt($card, '2026-04-15 23:50:00', 2000);
        $this->dumpCardAt($card, '2026-05-01 23:50:00', 0);

        $this->plannedCharge($card, 1600, '2026-06-20');
        $this->plannedCharge($card, 9000, '2026-08-01');

        $card->refresh();
        $card->unsetRelation('planned_payments');

        $this->assertSame(4100.0, $card->subSpendProgress());
        $this->assertTrue($card->has_satisfied_sub);
    }

    #[Test]
    public function matching_current_balance_and_latest_dump_are_not_double_counted(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 1500, pending: 0);

        $this->dumpCardAt($card, '2026-06-01 23:50:00', 1500);

        $card->refresh();

        $this->assertSame(1500.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function dumps_outside_the_sub_window_are_ignored(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 0, pending: 0);

        $this->dumpCardAt($card, '2026-03-01 23:50:00', 9000);
        $this->dumpCardAt($card, '2026-08-01 23:50:00', 9000);

        $card->refresh();

        $this->assertSame(0.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function expired_sub_is_satisfied_without_spend(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $card = Card::factory()->create([
            'date_opened' => '2025-01-01',
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 4000,
            'annual_fee' => 895,
            'balance' => 0,
            'pending' => 0,
        ]);

        $this->assertTrue($card->subBonusPeriodHasEnded());
        $this->assertTrue($card->has_satisfied_sub);
        $this->assertSame(0.0, $card->subSpendProgress());
        $this->assertFalse(Cache::has($card->subDumpSpendCacheKey()));
    }

    #[Test]
    public function annual_fee_is_subtracted_from_open_window_progress(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 4500, pending: 0, annualFee: 550);

        $this->assertSame(3950.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function annual_fee_does_not_drop_progress_below_zero(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 100, pending: 0, annualFee: 550);

        $this->assertSame(0.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function tracked_purchases_alone_do_not_satisfy_without_dumps_current_or_planned(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 0, pending: 0);

        $this->trackedPaidPurchase($card, 4000);

        $card->refresh();

        $this->assertSame(0.0, $card->subSpendProgress());
        $this->assertFalse($card->has_satisfied_sub);
    }

    #[Test]
    public function dump_spend_stays_cached_after_dumps_are_removed_until_the_next_due_date(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 200, pending: 0);

        $this->dumpCardAt($card, '2026-04-20 23:50:00', 2500);
        $this->dumpCardAt($card, '2026-05-20 23:50:00', 4100);
        $this->dumpCardAt($card, '2026-06-01 23:50:00', 0);

        $this->assertSame(4300.0, $card->subSpendProgress());
        $this->assertTrue(Cache::has($card->subDumpSpendCacheKey()));

        StateDump::query()->delete();
        $card->refresh();

        $this->assertSame(4300.0, $card->subSpendProgress());

        $card->update(['balance' => 800]);
        $card->refresh();

        $this->assertSame(4900.0, $card->subSpendProgress());
    }

    #[Test]
    public function dump_spend_cache_expires_after_the_card_due_date(): void
    {
        $card = $this->openSubCard(requirement: 4000, balance: 0, pending: 0, dueDate: 15);

        $this->dumpCardAt($card, '2026-05-20 23:50:00', 4100);
        $this->dumpCardAt($card, '2026-06-01 23:50:00', 0);

        $this->assertSame(4100.0, $card->subSpendProgress());

        StateDump::query()->delete();
        Carbon::setTestNow('2026-06-16 00:00:01');
        $card->refresh();

        $this->assertSame(0.0, $card->subSpendProgress());
    }

    private function openSubCard(int|float $requirement, float $balance, float $pending, int $dueDate = 28, float $annualFee = 0): Card
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        return Card::factory()->create([
            'date_opened' => '2026-04-01',
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => $requirement,
            'annual_fee' => $annualFee,
            'balance' => $balance,
            'pending' => $pending,
            'due_date' => $dueDate,
        ]);
    }

    private function dumpCardAt(Card $card, string $at, float $isb, float $balance = 0): void
    {
        $dump = StateDump::create([
            'data' => [
                Card::class => [
                    [
                        'id' => $card->id,
                        'interest_saving_balance' => $isb,
                        'balance' => $balance,
                        'pending' => 0,
                    ],
                ],
            ],
        ]);
        $dump->created_at = Carbon::parse($at);
        $dump->save();
    }

    private function trackedPaidPurchase(Card $card, float $amount): void
    {
        $spend = Spend::factory()->bare()->noPayments()->create();

        Payment::factory()->create([
            'card_id' => $card->id,
            'spend_id' => $spend->id,
            'spend_type' => getMorphAliasForClass(Spend::class),
            'amount' => $amount,
            'is_paid' => true,
            'paid_on' => '2026-05-10',
        ]);
    }

    private function plannedCharge(Card $card, float $amount, string $paidOn): void
    {
        $spend = Spend::factory()->bare()->noPayments()->create();

        Payment::factory()->create([
            'card_id' => $card->id,
            'spend_id' => $spend->id,
            'spend_type' => getMorphAliasForClass(Spend::class),
            'amount' => $amount,
            'is_paid' => false,
            'paid_on' => $paidOn,
        ]);
    }
}
