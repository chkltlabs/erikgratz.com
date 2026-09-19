<?php

namespace App\Filament\Resources\LoyaltyMembershipResource\RelationManagers;

use App\Filament\Forms\BookingPerkForm;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InheritedPerksRelationManager extends RelationManager
{
    protected static string $relationship = 'inheritedPerks';

    protected static ?string $title = 'Program-tier perks';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(BookingPerkForm::components(includeMinTier: true));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('min_tier')->placeholder('Any'),
                TextColumn::make('decision_value')->money()->label('Value'),
                TextColumn::make('applies_to')->badge(),
                TextColumn::make('channel')->placeholder('Any'),
                IconColumn::make('award_only')->boolean(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
