<?php

namespace App\Filament\Resources\LoyaltyMembershipResource\RelationManagers;

use App\Filament\Forms\BookingPerkForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerksRelationManager extends RelationManager
{
    protected static string $relationship = 'perks';

    protected static ?string $title = 'Membership perks';

    public function form(Schema $schema): Schema
    {
        return $schema->components(BookingPerkForm::components());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('decision_value')->money()->label('Value'),
                TextColumn::make('applies_to')->badge(),
                TextColumn::make('channel')->placeholder('Any'),
                IconColumn::make('award_only')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
