<?php

namespace App\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\RelationManagers\AccountsRelationManager;
use Filament\Schemas\Schema;
use App\Filament\Resources\SimpleFin\SimpleFinOrganizationResource\Pages;
use App\Models\SimpleFin\SimpleFinOrganization;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SimpleFinOrganizationResource extends Resource
{
    protected static ?string $model = SimpleFinOrganization::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static string | \UnitEnum | null $navigationGroup = 'SimpleFIN';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('domain')
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sfin_url')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('domain')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
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
                TextEntry::make('domain'),
                TextEntry::make('url'),
                TextEntry::make('sfin_url'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSimpleFinOrganizations::route('/'),
            'create' => Pages\CreateSimpleFinOrganization::route('/create'),
            'view' => Pages\ViewSimpleFinOrganization::route('/{record}'),
            'edit' => Pages\EditSimpleFinOrganization::route('/{record}/edit'),
        ];
    }
}
