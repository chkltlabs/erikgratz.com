<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Enums\PointsProgram;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\PointRedemption;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('paid_on')->nullable(),
                Forms\Components\Section::make('Types')
                    ->columns(3)
                    ->schema([
                        Select::make('type')
                            ->options(SpendType::asSelectArray())
                            ->afterStateUpdated(
                                function (?string $state, $get, $set) {
                                    if (! in_array($get('subtype'),
                                        SpendSubtype::getFilteredSet($state))) {
                                        $set('subtype', null);
                                    }
                                })
                            ->reactive()
                            ->required(),

                        Select::make('subtype')
                            ->options(
                                fn ($get) => SpendSubtype::getFilteredSet($get('type'))
                            )
                            ->reactive()
                            ->required(),
                        Select::make('points_program')
                            ->options(PointsProgram::asSelectArray()),
                ]),
                Forms\Components\Section::make('Points')
                    ->columns(3)
                    ->schema([
                        TextInput::make('points_spent')
                            ->numeric()
                            ->required(),
                        TextInput::make('money_spent')
                            ->prefix('$')
                            ->numeric()
                            ->required(),
                        TextInput::make('cash_value')
                            ->prefix('$')
                            ->numeric()
                            ->required(),
                    ]),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('paid_on')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('points_spent'),
                Tables\Columns\TextColumn::make('money_spent')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('cash_value')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('cents_per_point')
                    ->money('USD')
                    ->prefix('₵')
                    ->state(
                        fn (Model $record)
                        => (
                                (
                                    $record->cash_value
                                    - $record->money_spent
                                ) / $record->points_spent
                            ) * 100
                    ),


                Tables\Columns\TextColumn::make('type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subtype')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('points_program')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
