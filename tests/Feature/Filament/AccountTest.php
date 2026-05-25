<?php

namespace Tests\Feature\Filament;

use App\Enums\AccountType;
use App\Enums\CurrencyCode;
use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use App\Filament\Resources\AccountResource\Pages\ManageAccounts;
use Tests\Feature\Filament\Parent\SimpleResource;

class AccountTest extends SimpleResource
{
    public static string $modelClass = Account::class;
    public static string $pageClass = ManageAccounts::class;

    public static array $unsetAttributesBeforeCompare = ['display_name'];

    public function test_name_accessor_returns_computed_name_when_display_name_is_null(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => AccountType::Checking,
            'display_name' => null,
        ]);

        $this->assertEquals($user->name.' '.AccountType::Checking, $account->name);
    }

    public function test_name_accessor_returns_display_name_when_set(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => AccountType::Checking,
            'display_name' => 'My Custom Name',
        ]);

        $this->assertEquals('My Custom Name', $account->name);
    }
}
