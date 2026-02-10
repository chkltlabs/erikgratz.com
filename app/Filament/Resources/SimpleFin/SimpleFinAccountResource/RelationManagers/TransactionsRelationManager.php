<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->summarize(Sum::make()->money()),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payee')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_pending')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_pending'),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('posted', 'desc');
    }
}
