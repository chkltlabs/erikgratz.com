<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages\ListLoanAgainstSavings;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages\CreateLoanAgainstSavings;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages\EditLoanAgainstSavings;
use App\Filament\Resources\LoanAgainstSavingsResource\Pages;
use App\Filament\Resources\LoanAgainstSavingsResource\RelationManagers;
use App\Models\Card;
use App\Models\LoanAgainstSavings;
use App\Models\PeriodicSpend;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoanAgainstSavingsResource extends Resource
{
    protected static ?string $model = LoanAgainstSavings::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([

                    TextInput::make('balance')
                        ->required()
                        ->prefix('$')
                        ->numeric(),
                ]),
                Grid::make(1)->schema([
                    TextInput::make('reason')
                        ->maxLength(191),
                ]),
                Grid::make(3)->schema([
                    DatePicker::make('loan_date')
                        ->required(),
                    DatePicker::make('paid_on')
                        ->required(),
                    Toggle::make('is_paid')
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
                TextColumn::make('balance')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('reason'),
                TextColumn::make('loan_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('paid_on')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_paid')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListLoanAgainstSavings::route('/'),
            'create' => CreateLoanAgainstSavings::route('/create'),
            'edit' => EditLoanAgainstSavings::route('/{record}/edit'),
        ];
    }
}
