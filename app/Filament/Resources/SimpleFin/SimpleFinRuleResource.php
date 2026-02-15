<?php

namespace App\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinRuleResource\Pages;
use App\Models\SimpleFinRule;
use App\Filament\Forms\Fields\SpendAssociationField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SimpleFinRuleResource extends Resource
{
    protected static ?string $model = SimpleFinRule::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-funnel';

    protected static string | \UnitEnum | null $navigationGroup = 'SimpleFIN';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('pattern')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Substring match against transaction description or payee.'),
                SpendAssociationField::make('spend', true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pattern')
                    ->searchable(),
                Tables\Columns\TextColumn::make('spend.display_name')
                    ->label('Assigned To'),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListSimpleFinRules::route('/'),
            'create' => Pages\CreateSimpleFinRule::route('/create'),
            'edit' => Pages\EditSimpleFinRule::route('/{record}/edit'),
        ];
    }
}
