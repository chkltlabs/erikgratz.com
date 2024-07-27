<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AccountResource;
use App\Filament\Resources\CardResource;
use App\Models\Account;
use App\Models\Card;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CardsAndAccountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::first() ?? User::factory()->create());
    }

    public function test_card_manage_renders()
    {
        $this->get(CardResource::getUrl())->assertSuccessful();
    }

    public function test_card_can_create()
    {
        $model = Card::factory()->make();
        Livewire::test(CardResource\Pages\ManageCards::class)
            ->callAction('create', $model->toArray())
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_card_can_edit()
    {
        $existing = Card::factory()->create();
        $model = Card::factory()->make();
        Livewire::test(CardResource\Pages\ManageCards::class)
            ->callTableAction('edit', $existing, $model->toArray())
            ->assertHasNoTableActionErrors();
        $this->assertDatabaseHas($model->getTable(), $model->toArray());
    }

    public function test_card_can_delete()
    {
        $existing = Card::factory()->create();
        Livewire::test(CardResource\Pages\ManageCards::class)
            ->callTableAction('delete', $existing)
            ->assertHasNoTableActionErrors();
        $this->assertModelMissing($existing);
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
}
