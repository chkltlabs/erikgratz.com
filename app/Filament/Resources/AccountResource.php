<?php

namespace App\Filament\Resources;

use App\Enums\AccountType;
use App\Enums\CurrencyCode;
use App\Filament\Resources\AccountResource\Pages\ManageAccounts;
use App\Models\Account;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder as QueryBuilder;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->options(User::all()->pluck('name', 'id'))
                    ->required(),
                Select::make('type')
                    ->options(AccountType::asSelectArray())
                    ->required(),
                TextInput::make('display_name')
                    ->label('Display Name')
                    ->placeholder('Leave blank to use default')
                    ->maxLength(255),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('currency')
                    ->options(CurrencyCode::options())
                    ->default(CurrencyCode::USD->value)
                    ->required(),
                Select::make('simple_fin_account_id')
                    ->label('SimpleFIN Account')
                    ->options(function (?Account $record = null) {
                        $query = SimpleFinAccount::query();
                        if ($record && $record->user_id) {
                            $query->where('user_id', $record->user_id);
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (?Account $record = null) => $record?->simpleFinAccount?->id)
                    ->afterStateUpdated(function ($state, Account $record) {
                        // Unset previous association
                        SimpleFinAccount::where('associated_type', 'account')
                            ->where('associated_id', $record->id)
                            ->update([
                                'associated_id' => null,
                                'associated_type' => null,
                            ]);

                        if ($state) {
                            SimpleFinAccount::where('id', $state)->update([
                                'associated_id' => $record->id,
                                'associated_type' => 'account',
                            ]);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')-
                    ->searchable(),
                TextColumn::make('currency')
                    ->badge()
                    ->toggleable(),
                TextInputColumn::make('balance')
                    ->rules(['numeric'])
                    ->summarize(
                        Summarizer::make()
                            ->money('USD')
                            ->label('Total (USD)')
                            ->using(fn (QueryBuilder $query): float => Account::sumBalanceInUsd($query))
                    )
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordAction(null); // disables click-to-open behavior, can still edit with edit button
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccounts::route('/'),
        ];
    }
}
