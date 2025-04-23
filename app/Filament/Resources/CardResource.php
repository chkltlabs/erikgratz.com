<?php

namespace App\Filament\Resources;

use App\Enums\PointsProgram;
use App\Filament\Resources\CardResource\Pages;
use App\Filament\Resources\CardResource\RelationManagers\BenefitsRelationManager;
use App\Models\Card;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
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
                        Select::make('user_id')
                            ->label('Cardholder')
                            ->options(
                                User::all()
                                    ->pluck('name', 'id')
                            ),
                        ColorPicker::make('color'),
                    ]),
                Fieldset::make('Important Numbers')
                    ->columns(5)
                    ->schema([
                        TextInput::make('limit')
                            ->required()
                            ->numeric()
                            ->default(0),
                        DatePicker::make('date_opened')
                            ->required(),
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
                        Select::make('statement_date')
                            ->label('Statement closes on')
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
                        TextInput::make('annual_fee')
                            ->required()
                            ->numeric()
                            ->default(0),
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
                Fieldset::make('Points and Bonus')->columns(5)->schema([
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
                    TextInput::make('points_bonus_period'),
                    Select::make('points_program')
                        ->options(PointsProgram::asSelectArray()),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->sortable(),
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
                IconColumn::make('has_satisfied_sub')->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('balance', 'desc')
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
        //        return [
        //            'index' => Pages\ManageCards::route('/'),
        //        ];
        return [
            'index' => Pages\ListCards::route('/'),
            'create' => Pages\CreateCard::route('/create'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            BenefitsRelationManager::class,
        ];
    }
}
