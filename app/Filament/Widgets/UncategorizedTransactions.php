<?php

namespace App\Filament\Widgets;

use App\Models\SimpleFinRule;
use App\Models\SimpleFin\SimpleFinTransaction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UncategorizedTransactions extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Uncategorized Transactions';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SimpleFinTransaction::query()
                    ->whereNull('spend_id')
                    ->orderByDesc('posted')
            )
            ->columns([
                Tables\Columns\TextColumn::make('posted')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('payee'),
            ])
            ->actions([
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-link')
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
            ]);
    }
}
