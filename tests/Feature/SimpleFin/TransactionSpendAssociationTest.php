<?php

namespace Tests\Feature\SimpleFin;

use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\Spend;
use Database\Factories\SimpleFin\SimpleFinAccountFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSpendAssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_can_be_associated_with_spend()
    {
        $account = SimpleFinAccount::factory()->create();
        $txn = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
        ]);
        $spend = Spend::factory()->create();

        $txn->spend()->associate($spend);
        $txn->save();

        $this->assertNotNull($txn->fresh()->spend);
        $this->assertInstanceOf(Spend::class, $txn->fresh()->spend);
        $this->assertTrue($spend->transactions()->where('id', $txn->id)->exists());
    }

    public function test_transaction_can_be_associated_with_periodic_spend()
    {
        $account = SimpleFinAccount::factory()->create();
        $txn = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
        ]);
        $pSpend = PeriodicSpend::factory()->create();

        $txn->spend()->associate($pSpend);
        $txn->save();

        $this->assertNotNull($txn->fresh()->spend);
        $this->assertInstanceOf(PeriodicSpend::class, $txn->fresh()->spend);
        $this->assertTrue($pSpend->transactions()->where('id', $txn->id)->exists());
    }
}
