<?php

namespace Tests\Feature;

use App\Jobs\DumpState;
use App\Models\Card;
use App\Models\StateDump;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DumpStateTest extends TestCase
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
    public function dump_prefills_isb_spend_cache_only_for_cards_due_yesterday(): void
    {
        Carbon::setTestNow('2026-06-16 23:50:00');

        $dueYesterday = $this->subCard(dueDate: 15, balance: 200, isb: 0);
        $other = $this->subCard(dueDate: 28, balance: 50, isb: 900);

        $this->dumpIsbAt($dueYesterday, '2026-06-14 23:50:00', 4100);
        $this->dumpIsbAt($other, '2026-06-14 23:50:00', 900);

        Cache::put($dueYesterday->subDumpSpendCacheKey(), 0.0, now()->addDay());
        Cache::put($other->subDumpSpendCacheKey(), 9999.0, now()->addDay());

        StateDump::dump();

        $this->assertSame(4100.0, (float) Cache::get($dueYesterday->subDumpSpendCacheKey()));
        $this->assertSame(9999.0, (float) Cache::get($other->subDumpSpendCacheKey()));
        $this->assertSame(4300.0, $dueYesterday->fresh()->subSpendProgress());
    }

    #[Test]
    public function dump_on_due_date_does_not_prefill_that_card(): void
    {
        Carbon::setTestNow('2026-06-15 23:50:00');

        $dueToday = $this->subCard(dueDate: 15, balance: 0, isb: 0);
        $this->dumpIsbAt($dueToday, '2026-06-14 23:50:00', 4100);

        Cache::put($dueToday->subDumpSpendCacheKey(), 0.0, now()->addDay());

        StateDump::dump();

        $this->assertSame(0.0, (float) Cache::get($dueToday->subDumpSpendCacheKey()));
    }

    #[Test]
    public function dump_state_job_dumps_when_flagged_and_prefills_yesterday_due_cards(): void
    {
        Carbon::setTestNow('2026-06-16 23:50:00');

        $dueYesterday = $this->subCard(dueDate: 15, balance: 0, isb: 0);
        $this->dumpIsbAt($dueYesterday, '2026-06-14 23:50:00', 4100);
        Cache::put($dueYesterday->subDumpSpendCacheKey(), 0.0, now()->addDay());

        StateDump::setShouldDumpFlag();
        $countBefore = StateDump::count();

        (new DumpState)->handle();

        $this->assertSame($countBefore + 1, StateDump::count());
        $this->assertFalse(Cache::has(StateDump::SHOULD_DUMP));
        $this->assertSame(4100.0, (float) Cache::get($dueYesterday->subDumpSpendCacheKey()));
    }

    #[Test]
    public function dump_state_job_does_nothing_without_the_dump_flag(): void
    {
        Carbon::setTestNow('2026-06-16 23:50:00');

        $dueYesterday = $this->subCard(dueDate: 15, balance: 0, isb: 0);
        Cache::put($dueYesterday->subDumpSpendCacheKey(), 0.0, now()->addDay());
        Cache::forget(StateDump::SHOULD_DUMP);

        $countBefore = StateDump::count();
        (new DumpState)->handle();

        $this->assertSame($countBefore, StateDump::count());
        $this->assertSame(0.0, (float) Cache::get($dueYesterday->subDumpSpendCacheKey()));
    }

    private function subCard(int $dueDate, float $balance, float $isb): Card
    {
        return Card::factory()->create([
            'date_opened' => '2026-04-01',
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 4000,
            'annual_fee' => 0,
            'due_date' => $dueDate,
            'balance' => $balance,
            'pending' => 0,
            'interest_saving_balance' => $isb,
        ]);
    }

    private function dumpIsbAt(Card $card, string $at, float $isb): void
    {
        $dump = StateDump::create([
            'data' => [
                Card::class => [
                    [
                        'id' => $card->id,
                        'interest_saving_balance' => $isb,
                        'balance' => 0,
                        'pending' => 0,
                    ],
                ],
            ],
        ]);
        $dump->created_at = Carbon::parse($at);
        $dump->save();
    }
}
