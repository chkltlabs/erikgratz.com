<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AccountResource;
use App\Filament\Resources\CardResource;
use App\Models\Account;
use App\Models\Card;
use App\Models\User;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinOrganization;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CardsAndAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_card_manage_renders()
    {
        $this->get(CardResource::getUrl())->assertSuccessful();
    }

    public function test_card_can_create()
    {
        $model = Card::factory()->make();
        Livewire::test(CardResource\Pages\CreateCard::class)
            ->fillForm($model->toArray())
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_card_can_edit()
    {
        $existing = Card::factory()->create();
        $model = Card::factory()->make();
        Livewire::test(CardResource\Pages\EditCard::class, ['record' => $existing->getKey()])
            ->fillForm($model->toArray())
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_card_can_delete()
    {
        $existing = Card::factory()->create();
        Livewire::test(CardResource\Pages\EditCard::class, ['record' => $existing->getKey()])
            ->callAction('delete')
            ->assertHasNoErrors();
        $this->assertDatabaseMissing($existing->getTable(), $existing->toArray());
    }

    public function test_card_can_associate_simple_fin_account()
    {
        $user = auth()->user();
        $card = Card::factory()->create(['user_id' => $user->id]);
        $org = SimpleFinOrganization::create(['id' => 'org-1', 'name' => 'Org']);
        $sfinAccount = SimpleFinAccount::create([
            'id' => 'sfin-1',
            'user_id' => $user->id,
            'simple_fin_organization_id' => $org->id,
            'name' => 'SFIN Account',
            'currency' => 'USD',
            'balance' => 100,
            'available_balance' => 100,
            'balance_date' => now(),
        ]);

        Livewire::test(CardResource\Pages\EditCard::class, ['record' => $card->getKey()])
            ->fillForm(['simple_fin_account_id' => $sfinAccount->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('simple_fin_accounts', [
            'id' => $sfinAccount->id,
            'associated_id' => $card->id,
            'associated_type' => 'card',
        ]);
    }

    public function test_acct_manage_renders()
    {
        $this->get(AccountResource::getUrl())->assertSuccessful();
    }

    public function test_acct_can_create()
    {
        $model = Account::factory()->make();
        Livewire::test(AccountResource\Pages\ManageAccounts::class)
            ->callAction('create', $model->toArray())
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_acct_can_edit()
    {
        $existing = Account::factory()->create();
        $model = Account::factory()->make();
        Livewire::test(AccountResource\Pages\ManageAccounts::class)
            ->callTableAction('edit', $existing, $model->toArray())
            ->assertHasNoTableActionErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_acct_can_delete()
    {
        $existing = Account::factory()->create();
        Livewire::test(AccountResource\Pages\ManageAccounts::class)
            ->callTableAction('delete', $existing)
            ->assertHasNoTableActionErrors();
        $this->assertModelMissing($existing);
    }

    public function test_acct_can_associate_simple_fin_account()
    {
        $user = auth()->user();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $org = SimpleFinOrganization::create(['id' => 'org-2', 'name' => 'Org']);
        $sfinAccount = SimpleFinAccount::create([
            'id' => 'sfin-2',
            'user_id' => $user->id,
            'simple_fin_organization_id' => $org->id,
            'name' => 'SFIN Account 2',
            'currency' => 'USD',
            'balance' => 200,
            'available_balance' => 200,
            'balance_date' => now(),
        ]);

        Livewire::test(AccountResource\Pages\ManageAccounts::class)
            ->callTableAction('edit', $account, ['simple_fin_account_id' => $sfinAccount->id])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('simple_fin_accounts', [
            'id' => $sfinAccount->id,
            'associated_id' => $account->id,
            'associated_type' => 'account',
        ]);
    }
}
