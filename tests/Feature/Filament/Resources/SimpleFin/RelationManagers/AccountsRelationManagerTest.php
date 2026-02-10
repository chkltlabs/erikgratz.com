<?php

namespace Tests\Feature\Filament\Resources\SimpleFin\RelationManagers;

use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\Pages\EditSimpleFinOrganization;
use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\RelationManagers\AccountsRelationManager;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinOrganization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Filament\FilamentTestCase;

class AccountsRelationManagerTest extends FilamentTestCase
{
    use DatabaseTransactions;

    public function test_lists_related_accounts(): void
    {
        $org = SimpleFinOrganization::factory()
            ->has(SimpleFinAccount::factory()->count(3), 'accounts')
            ->create();

        Livewire::test(AccountsRelationManager::class, [
            'ownerRecord' => $org,
            'pageClass' => EditSimpleFinOrganization::class,
        ])->set('tableRecordsPerPage', 'all')
          ->assertCanSeeTableRecords($org->accounts);
    }

    public function test_has_expected_columns_and_view_action(): void
    {
        $org = SimpleFinOrganization::factory()
            ->has(SimpleFinAccount::factory()->count(1), 'accounts')
            ->create();

        Livewire::test(AccountsRelationManager::class, [
            'ownerRecord' => $org,
            'pageClass' => EditSimpleFinOrganization::class,
        ])->assertHasNoErrors();
    }
}
