<?php

namespace Tests\Feature\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\Spend;
use App\Models\Activity;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\SimpleFin\SimpleFinAccountResource\Pages\ViewSimpleFinAccount;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SimpleFinTransactionPrepopulationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_spend_association_is_prepopulated_with_spend()
    {
        $activity = Activity::factory()->create(['name' => 'Test Activity']);
        $spend = Spend::factory()->create([
            'name' => 'Test Spend',
            'activity_id' => $activity->id,
        ]);
        $transaction = SimpleFinTransaction::factory()->create();
        $transaction->spend()->associate($spend);
        $transaction->save();

        Livewire::test(SimpleFinTransactionResource\Pages\EditSimpleFinTransaction::class, [
            'record' => $transaction->getRouteKey(),
        ])
            ->assertFormSet([
                'spend_type' => $spend->getMorphClass(),
                'spend_id' => $spend->id,
            ]);
    }

    public function test_spend_association_is_prepopulated_with_periodic_spend()
    {
        $periodicSpend = PeriodicSpend::factory()->create([
            'name' => 'Test Periodic Spend',
        ]);
        $transaction = SimpleFinTransaction::factory()->create();
        $transaction->spend()->associate($periodicSpend);
        $transaction->save();

        Livewire::test(SimpleFinTransactionResource\Pages\EditSimpleFinTransaction::class, [
            'record' => $transaction->getRouteKey(),
        ])
            ->assertFormSet([
                'spend_type' => $periodicSpend->getMorphClass(),
                'spend_id' => $periodicSpend->id,
            ]);
    }

    public function test_spend_association_is_prepopulated_in_relation_manager_modal()
    {
        $activity = Activity::factory()->create(['name' => 'Test Activity']);
        $spend = Spend::factory()->create([
            'name' => 'Test Spend',
            'activity_id' => $activity->id,
        ]);

        $account = SimpleFinAccount::factory()->create();
        $transaction = SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
        ]);
        $transaction->spend()->associate($spend);
        $transaction->save();

        Livewire::test(TransactionsRelationManager::class, [
            'ownerRecord' => $account,
            'pageClass' => ViewSimpleFinAccount::class,
        ])
            ->mountTableAction('assign', $transaction)
            ->assertTableActionDataSet([
                'spend_type' => $spend->getMorphClass(),
                'spend_id' => $spend->id,
            ]);
    }
}
