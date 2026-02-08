<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Filament\Resources\AccountResource\Pages\ManageAccounts;
use Tests\Feature\Filament\Parent\SimpleResource;

class AccountTest extends SimpleResource
{
    public static string $modelClass = Account::class;
    public static string $pageClass = ManageAccounts::class;
}
