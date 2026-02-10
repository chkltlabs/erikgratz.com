<?php

namespace App\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource\Pages;
use App\Filament\Forms\Fields\SpendAssociationField;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\Spend;
use App\Models\PeriodicSpend;
use Carbon\Carbon;
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

class SimpleFinTransactionResource extends Resource
{
    protected static ?string $model = SimpleFinTransaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string | \UnitEnum | null $navigationGroup = 'SimpleFIN';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('simple_fin_account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Forms\Components\DateTimePicker::make('posted')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payee')
                    ->maxLength(255),
                Forms\Components\Textarea::make('memo')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('transacted_at'),
                Forms\Components\Toggle::make('is_pending')
                    ->required(),
                SpendAssociationField::make('spend', false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()->money()),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payee')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_pending')
                    ->boolean(),
                Tables\Columns\TextColumn::make('transacted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('spend_display')
                    ->label('Associated To')
                    ->state(function (SimpleFinTransaction $record) {
                        if (! $record->spend) {
                            return '—';
                        }
                        $label = $record->spend instanceof Spend ? 'Spend' : 'Periodic Spend';
                        $name = method_exists($record->spend, 'name') ? $record->spend->name : '';
                        if ($record->spend instanceof Spend && $record->spend->activity) {
                            $name = $record->spend->activity->name . ' • ' . $name;
                        }
                        return $label . ': ' . $name;
                    })
                    ->searchable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account')
                    ->relationship('account', 'name'),
                Tables\Filters\TernaryFilter::make('is_pending'),
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
                TextEntry::make('posted')
                    ->dateTime(),
                TextEntry::make('amount')
                    ->money(),
                TextEntry::make('description'),
                TextEntry::make('payee'),
                TextEntry::make('account.name'),
                TextEntry::make('memo'),
                TextEntry::make('transacted_at')
                    ->dateTime(),
                TextEntry::make('is_pending')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
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
            'index' => Pages\ListSimpleFinTransactions::route('/'),
            'create' => Pages\CreateSimpleFinTransaction::route('/create'),
            'view' => Pages\ViewSimpleFinTransaction::route('/{record}'),
            'edit' => Pages\EditSimpleFinTransaction::route('/{record}/edit'),
        ];
    }
}
