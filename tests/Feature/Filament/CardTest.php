<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CardResource;
use App\Models\Card;
use App\Filament\Resources\CardResource\Pages\ListCards;
use App\Filament\Resources\CardResource\Pages\CreateCard;
use App\Filament\Resources\CardResource\Pages\EditCard;
use Tests\Feature\Filament\Parent\Resource;

class CardTest extends Resource
{
    public static string $resourceClass = CardResource::class;
    public static string $modelClass = Card::class;
    public static string $listPage = ListCards::class;
    public static string $createPage = CreateCard::class;
    public static string $editPage = EditCard::class;

    public static array $unsetAttributesBeforeCompare = ['user_id', 'interest_saving_balance', 'balance', 'limit', 'annual_fee', 'pending', 'interest_free_balance', 'interest_free_balance_payment'];
}
