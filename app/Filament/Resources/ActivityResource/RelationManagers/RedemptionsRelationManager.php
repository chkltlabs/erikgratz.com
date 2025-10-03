<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Enums\PointsProgram;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\PointRedemption;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('paid_on')->nullable(),
                Section::make('Types')
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
                Section::make('Points')
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
                TextColumn::make('name'),
                TextColumn::make('paid_on')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('points_spent'),
                TextColumn::make('money_spent')
                    ->money('USD'),
                TextColumn::make('cash_value')
                    ->money('USD'),
                TextColumn::make('cents_per_point')
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


                TextColumn::make('type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subtype')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('points_program')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
