<?php

namespace App\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\SimpleFin\SimpleFinAccountResource\Pages;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

class SimpleFinAccountResource extends Resource
{
    protected static ?string $model = SimpleFinAccount::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static string | \UnitEnum | null $navigationGroup = 'SimpleFIN';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->options(User::all()->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('simple_fin_organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('available_balance')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('balance_date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()->money()),
                Tables\Columns\TextColumn::make('available_balance')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()->money()),
                Tables\Columns\TextColumn::make('balance_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('user.name'),
                TextEntry::make('organization.name'),
                TextEntry::make('currency'),
                TextEntry::make('balance')
                    ->money(),
                TextEntry::make('available_balance')
                    ->money(),
                TextEntry::make('balance_date')
                    ->dateTime(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSimpleFinAccounts::route('/'),
            'create' => Pages\CreateSimpleFinAccount::route('/create'),
            'view' => Pages\ViewSimpleFinAccount::route('/{record}'),
            'edit' => Pages\EditSimpleFinAccount::route('/{record}/edit'),
        ];
    }
}
