<?php

namespace App\Filament\Resources\SimpleFin;

use App\Filament\Resources\SimpleFin\SimpleFinTransactionResource\Pages;
use App\Filament\Forms\Fields\SpendAssociationField;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\Spend;
use App\Models\SimpleFinRule;
use App\Services\SimpleFin\SimpleFinCategorizationService;
use App\Services\SimpleFin\SimpleFinIntakeService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
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
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Confirmed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('spend.display_name')
                    ->label('Assigned To'),
                Tables\Columns\TextColumn::make('transacted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account')
                    ->relationship('account', 'name'),
                Tables\Filters\TernaryFilter::make('is_pending'),
                Tables\Filters\TernaryFilter::make('is_confirmed'),
                Tables\Filters\TernaryFilter::make('assigned')
                    ->placeholder('All')
                    ->trueLabel('Assigned')
                    ->falseLabel('Unassigned')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('spend_id'),
                        false: fn ($query) => $query->whereNull('spend_id'),
                    ),
            ])
            ->headerActions([
                Action::make('sync')
                    ->label('Sync & Auto-match')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user) {
                            SimpleFinIntakeService::fetchAndIntake($user);
                            Notification::make()
                                ->title('Transactions synced and auto-categorized')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('recategorize')
                    ->label('Re-categorize All')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('This will run the auto-categorization engine on all unconfirmed transactions.')
                    ->action(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user) {
                            SimpleFinCategorizationService::categorize($user, true);
                            Notification::make()
                                ->title('Auto-categorization completed')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SimpleFinTransaction $record): bool => $record->spend_id !== null && !$record->is_confirmed)
                    ->action(fn (SimpleFinTransaction $record) => $record->update(['is_confirmed' => true])),
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-link')
                    ->schema([
                        SpendAssociationField::make('spend', true),
                        Forms\Components\Toggle::make('create_rule')
                            ->label('Save as rule for future transactions')
                            ->live(),
                        Forms\Components\TextInput::make('rule_pattern')
                            ->label('Matching Pattern')
                            ->placeholder('e.g. Netflix')
                            ->required(fn (Get $get) => $get('create_rule'))
                            ->visible(fn (Get $get) => $get('create_rule')),
                    ])
                    ->fillForm(fn (SimpleFinTransaction $record): array => [
                        'spend_type' => $record->spend_type,
                        'spend_id' => $record->spend_id,
                        'rule_pattern' => $record->payee ?: $record->description,
                    ])
                    ->action(function (SimpleFinTransaction $record, array $data) {
                        $record->update([
                            'spend_type' => $data['spend_type'],
                            'spend_id' => $data['spend_id'],
                            'is_confirmed' => true,
                        ]);

                        if ($data['create_rule'] ?? false) {
                            SimpleFinRule::create([
                                'pattern' => $data['rule_pattern'],
                                'spend_type' => $data['spend_type'],
                                'spend_id' => $data['spend_id'],
                            ]);

                            Notification::make()
                                ->title('Rule created')
                                ->success()
                                ->send();
                        }
                    })
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
