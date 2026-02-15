<?php

namespace App\Filament\Widgets;

use App\Models\SimpleFinRule;
use App\Models\SimpleFin\SimpleFinTransaction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingReviewTransactions extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Transactions Pending Review';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SimpleFinTransaction::query()
                    ->whereNotNull('spend_id')
                    ->where('is_confirmed', false)
            )
            ->columns([
                Tables\Columns\TextColumn::make('posted')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('payee'),
                Tables\Columns\TextColumn::make('spend.display_name')
                    ->label('Suggested Match'),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (SimpleFinTransaction $record) => $record->update(['is_confirmed' => true])),
                Action::make('assign')
                    ->label('Re-assign')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        \App\Filament\Forms\Fields\SpendAssociationField::make('spend', true),
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
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn (SimpleFinTransaction $record) => $record->update([
                        'spend_type' => null,
                        'spend_id' => null,
                    ])),
            ])
            ->bulkActions([
                BulkAction::make('confirm_selected')
                    ->label('Confirm Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (\Illuminate\Support\Collection $records) => $records->each->update(['is_confirmed' => true])),
            ]);
    }
}
