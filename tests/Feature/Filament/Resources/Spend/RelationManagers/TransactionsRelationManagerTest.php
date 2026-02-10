<?php

namespace Tests\Feature\Filament\Resources\Spend\RelationManagers;

use App\Filament\Resources\SpendResource\Pages\EditSpend;
use App\Filament\Resources\SpendResource\RelationManagers\TransactionsRelationManager;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\Spend;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;

class TransactionsRelationManagerTest extends FilamentTestCase
{
    use DatabaseTransactions;

    public function test_attach_transactions_header_action_associates_selected_transactions(): void
    {
        $spend = Spend::factory()->create();
        $unattached = SimpleFinTransaction::factory()->count(3)->create();

        // Sanity: all should be unattached
        $this->assertEquals(3, SimpleFinTransaction::whereNull('spend_id')->count());

        Livewire::test(TransactionsRelationManager::class, [
            'ownerRecord' => $spend,
            'pageClass' => EditSpend::class,
        ])->callTableAction('attachTransactions', data: [
            'transactions' => $unattached->pluck('id')->take(2)->all(),
        ])->assertHasNoTableActionErrors();

        $this->assertEquals(1, SimpleFinTransaction::whereNull('spend_id')->count());
        $this->assertEquals(2, $spend->transactions()->count());
    }
}
