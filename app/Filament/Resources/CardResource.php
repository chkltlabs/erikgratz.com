<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CardResource\Pages;
use App\Filament\Resources\CardResource\RelationManagers\PlannedPaymentsRelationManager;
use App\Models\Card;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Basic Info')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(191),
                        TextInput::make('limit')
                            ->required()
                            ->numeric()
                            ->default(0),
                        ColorPicker::make('color'),
                        DatePicker::make('date_opened'),
                        Select::make('due_date')
                            ->label('Due on')
                            ->options(
                                array_combine(
                                    range(1, 31),
                                    array_map(
                                        fn ($num) => now()->day($num)->format('jS'),
                                        range(1, 31)
                                    )
                                )
                            )
                            ->required(),
                    ]),
                Fieldset::make('Balance and Interest')
                    ->columns(5)
                    ->schema([
                        TextInput::make('balance')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('pending')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('interest_saving_balance')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('interest_free_balance')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('interest_free_balance_payment')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),
                Fieldset::make('Points and Bonus')->columns(4)->schema([
                    TextInput::make('points_balance')
                        ->required()
                        ->numeric()
                        ->default(0),
                    TextInput::make('points_bonus')
                        ->required()
                        ->numeric()
                        ->default(0),
                    TextInput::make('points_bonus_spend')
                        ->required()
                        ->numeric()
                        ->default(0),
                    TextInput::make('points_bonus_period')
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('limit')
                    ->money()
                    ->summarize(Sum::make()->money()->label(''))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('balance')
                    ->money()
                    ->summarize(Sum::make()->money()->label(''))
                    ->sortable(),
                TextColumn::make('pending')
                    ->money()
                    ->summarize(Sum::make()->money()->label(''))
                    ->sortable(),
                TextColumn::make('interest_saving_balance')
                    ->label('ISB')
                    ->money()
                    ->summarize(Sum::make()->money()->label(''))
                    ->sortable(),
                TextColumn::make('interest_free_balance')
                    ->label('0% Bal')
                    ->money()
                    ->summarize(Sum::make()->money()->label(''))
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->formatStateUsing(fn ($state): string => now()->day($state)->format('jS'))
                    ->sortable(),
                TextColumn::make('plannedPaymentTotal')
                    ->money()
                    ->sortable(),
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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCards::route('/'),
        ];
    }
}
