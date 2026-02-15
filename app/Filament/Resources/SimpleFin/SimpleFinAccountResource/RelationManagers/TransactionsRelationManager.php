<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers;

use App\Filament\Forms\Fields\SpendAssociationField;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\SimpleFinRule;
use App\Models\Spend;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use App\Services\SimpleFin\SimpleFinCategorizationService;
use App\Services\SimpleFin\SimpleFinIntakeService;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->summarize(Sum::make()->money()),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payee')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_pending')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Confirmed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('spend.display_name')
                    ->label('Assigned To'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_pending'),
                Tables\Filters\TernaryFilter::make('is_confirmed'),
                Tables\Filters\TernaryFilter::make('assigned')
                    ->placeholder('All')
                    ->trueLabel('Assigned')
                    ->falseLabel('Unassigned')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('spend_id'),
                        false: fn (Builder $query) => $query->whereNull('spend_id'),
                    ),
            ])
            ->headerActions([
                Action::make('sync')
                    ->label('Sync & Auto-match')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (RelationManager $livewire) {
                        SimpleFinIntakeService::fetchAndIntake($livewire->getOwner()->user);
                        Notification::make()
                            ->title('Transactions synced and auto-categorized')
                            ->success()
                            ->send();
                    })
            ])
            ->recordActions([
                ViewAction::make(),
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
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm_selected')
                        ->label('Confirm Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_confirmed' => true])),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByDesc('is_pending')
                ->orderByDesc('posted')
            );
    }
}
