<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanAgainstSavingsResource\Pages;
use App\Filament\Resources\LoanAgainstSavingsResource\RelationManagers;
use App\Models\Card;
use App\Models\LoanAgainstSavings;
use App\Models\PeriodicSpend;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoanAgainstSavingsResource extends Resource
{
    protected static ?string $model = LoanAgainstSavings::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([

                    Forms\Components\TextInput::make('balance')
                        ->required()
                        ->prefix('$')
                        ->numeric(),
                ]),
                Forms\Components\Grid::make(1)->schema([
                    Forms\Components\TextInput::make('reason')
                        ->maxLength(191),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\DatePicker::make('loan_date')
                        ->required(),
                    Forms\Components\DatePicker::make('paid_on')
                        ->required(),
                    Forms\Components\Toggle::make('is_paid')
                        ->required(),
                    Select::make('card_id')
                        ->label('Paid to Card')
                        ->options(Card::all()->pluck('name', 'id'))
                        ->nullable(),
                ]),
                Grid::make(1)->schema([
                    LoanAgainstSavings::paymentFilamentFormComponent(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason'),
                Tables\Columns\TextColumn::make('loan_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanAgainstSavings::route('/'),
            'create' => Pages\CreateLoanAgainstSavings::route('/create'),
            'edit' => Pages\EditLoanAgainstSavings::route('/{record}/edit'),
        ];
    }
}
