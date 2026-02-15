<?php

namespace Tests\Feature\Filament\Resources\SimpleFin\RelationManagers;

use App\Filament\Resources\SimpleFin\SimpleFinAccountResource\Pages\EditSimpleFinAccount;
use App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers\TransactionsRelationManager;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;

class TransactionsRelationManagerTest extends FilamentTestCase
{
    use DatabaseTransactions;

    public function test_pending_transactions_appear_first()
    {
        $account = SimpleFinAccount::factory()->create();

        // A non-pending, recent transaction
        $postedTxn = SimpleFinTransaction::factory()
            ->recycle($account)
            ->create([
                'description' => 'POSTED TXN',
                'posted' => now()->subDay(),
                'is_pending' => false,
            ]);

        // A pending transaction with an epoch-ish posted date (normalized value)
        $pendingTxn = SimpleFinTransaction::factory()
            ->recycle($account)
            ->create([
                'description' => 'PENDING TXN',
                'posted' => now()->setTimestamp(1),
                'is_pending' => true,
            ]);

        Livewire::test(TransactionsRelationManager::class, [
            'ownerRecord' => $account,
            'pageClass' => EditSimpleFinAccount::class,
        ])
            ->set('tableRecordsPerPage', 'all')
            // Ensure the pending transaction row is rendered before the posted one
            ->assertSeeInOrder([
                'PENDING TXN',
                'POSTED TXN',
            ]);
    }

    public function test_can_assign_transaction_and_create_rule()
    {
        $account = SimpleFinAccount::factory()->create();
        $transaction = SimpleFinTransaction::factory()->recycle($account)->create([
            'description' => 'Netflix.com',
            'is_confirmed' => false,
        ]);
        $spend = \App\Models\Spend::factory()->create();

        Livewire::test(TransactionsRelationManager::class, [
            'ownerRecord' => $account,
            'pageClass' => EditSimpleFinAccount::class,
        ])
            ->callTableAction('assign', $transaction, data: [
                'spend_type' => 'spend',
                'spend_id' => $spend->id,
                'create_rule' => true,
                'rule_pattern' => 'Netflix',
            ])
            ->assertHasNoTableActionErrors();

        $transaction->refresh();
        $this->assertTrue($transaction->is_confirmed);
        $this->assertEquals($spend->id, $transaction->spend_id);

        $this->assertDatabaseHas('simple_fin_rules', [
            'pattern' => 'Netflix',
            'spend_id' => $spend->id,
            'spend_type' => 'spend',
        ]);
    }
}
