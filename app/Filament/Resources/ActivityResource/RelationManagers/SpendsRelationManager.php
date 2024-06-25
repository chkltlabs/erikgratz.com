<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Filament\Resources\SpendResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SpendsRelationManager extends RelationManager
{
    protected static string $relationship = 'spends';

    public function form(Form $form): Form
    {
        return $form
            ->schema(SpendResource::formSchema());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(SpendResource::tableSchema())
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
