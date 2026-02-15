<?php

namespace Tests\Feature\SimpleFin;

use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\SimpleFinRule;
use App\Models\Spend;
use App\Models\User;
use App\Services\SimpleFin\SimpleFinCategorizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SimpleFinCategorizationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SimpleFinAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = SimpleFinAccount::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_it_categorizes_transactions_using_rules(): void
    {
        $spend = Spend::factory()->create();
        SimpleFinRule::factory()->create([
            'pattern' => 'Netflix',
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
        ]);

        $transaction = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $this->account->id,
            'description' => 'NETFLIX.COM PAYMENT',
            'spend_id' => null,
        ]);

        SimpleFinCategorizationService::categorize($this->user);

        $transaction->refresh();
        $this->assertEquals($spend->id, $transaction->spend_id);
        $this->assertEquals('spend', $transaction->spend_type);
        $this->assertFalse($transaction->is_confirmed);
    }

    public function test_it_categorizes_transactions_using_balance_match(): void
    {
        $spend = Spend::factory()->create();
        $paymentDate = Carbon::now()->day(15);

        // Create an unpaid payment for this month
        Payment::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'amount' => 123.45,
            'is_paid' => false,
            'paid_on' => $paymentDate,
        ]);

        $transaction = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $this->account->id,
            'amount' => -123.45,
            'posted' => $paymentDate->copy()->addDays(1),
            'spend_id' => null,
        ]);

        SimpleFinCategorizationService::categorize($this->user);

        $transaction->refresh();
        $this->assertEquals($spend->id, $transaction->spend_id);
        $this->assertEquals('spend', $transaction->spend_type);
        $this->assertFalse($transaction->is_confirmed);
    }

    public function test_it_does_not_match_if_already_paid(): void
    {
        $spend = Spend::factory()->create();
        $paymentDate = Carbon::now()->day(15);

        Payment::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'amount' => 123.45,
            'is_paid' => true,
            'paid_on' => $paymentDate,
        ]);

        $transaction = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $this->account->id,
            'amount' => -123.45,
            'posted' => $paymentDate->copy()->addDays(1),
            'spend_id' => null,
        ]);

        SimpleFinCategorizationService::categorize($this->user);

        $transaction->refresh();
        $this->assertNull($transaction->spend_id);
    }

    public function test_it_does_not_match_if_outside_month(): void
    {
        $spend = Spend::factory()->create();
        $paymentDate = Carbon::now()->subMonth()->day(15);

        Payment::factory()->create([
            'spend_type' => 'spend',
            'spend_id' => $spend->id,
            'amount' => 123.45,
            'is_paid' => false,
            'paid_on' => $paymentDate,
        ]);

        $transaction = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $this->account->id,
            'amount' => -123.45,
            'posted' => Carbon::now()->day(1), // Current month
            'spend_id' => null,
        ]);

        SimpleFinCategorizationService::categorize($this->user);

        $transaction->refresh();
        $this->assertNull($transaction->spend_id);
    }
}
