<?php

declare(strict_types=1);

namespace App\Filament\Resources\CardBenefits;

use App\Filament\Resources\CardBenefits\Pages\ListCardBenefits;
use App\Filament\Resources\CardBenefits\Tables\CardBenefitsTable;
use App\Models\CardBenefit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CardBenefitResource extends Resource
{
    protected static ?string $model = CardBenefit::class;

    protected static ?string $slug = 'benefits-due';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Travel wallet';

    protected static ?string $navigationLabel = 'Benefits due';

    protected static ?string $modelLabel = 'benefit';

    protected static ?string $pluralModelLabel = 'Unused benefits by expiry';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return CardBenefitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCardBenefits::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
