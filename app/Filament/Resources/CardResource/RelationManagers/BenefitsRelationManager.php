<?php

namespace App\Filament\Resources\CardResource\RelationManagers;

use App\Enums\ResetPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BenefitsRelationManager extends RelationManager
{
    protected static string $relationship = 'benefits';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('benefit')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_useable'),
                    Forms\Components\Toggle::make('is_used'),
                ]),
                    Forms\Components\Grid::make(1)->schema([
                    Forms\Components\Textarea::make('description')
                    ->maxLength(2048),
                ]),
                Forms\Components\TextInput::make('value')
                    ->numeric(),
                Forms\Components\Select::make('reset_period')
                    ->options(ResetPeriod::asSelectArray())
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('benefit')
            ->columns([
                Tables\Columns\TextColumn::make('benefit')
                    ->tooltip(fn (Model $record): ?string => $record->description),
                Tables\Columns\IconColumn::make('is_useable'),
                Tables\Columns\ToggleColumn::make('is_used')
                    ->disabled(fn (?Model $record) => !$record?->is_useable),
                Tables\Columns\TextColumn::make('value')->money(),
                Tables\Columns\TextColumn::make('reset_period'),
            ])
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
