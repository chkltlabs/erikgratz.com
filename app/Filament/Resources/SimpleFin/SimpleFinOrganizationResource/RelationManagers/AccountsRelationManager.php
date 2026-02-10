<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;

class AccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'accounts';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('balance')
                    ->money()
                    ->summarize(Sum::make()->money()),
                Tables\Columns\TextColumn::make('available_balance')
                    ->money()
                    ->summarize(Sum::make()->money()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
