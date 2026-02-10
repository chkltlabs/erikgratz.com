<?php

namespace App\Filament\Resources\SpendResource\RelationManagers;

use App\Models\SimpleFin\SimpleFinTransaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;

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
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_pending'),
            ])
            ->headerActions([
                Action::make('attachTransactions')
                    ->label('Attach Transactions')
                    ->icon('heroicon-o-link')
                    ->form([
                        \Filament\Forms\Components\Select::make('transactions')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $query) {
                                return SimpleFinTransaction::query()
                                    ->whereNull('spend_id')
                                    ->where(function ($q) use ($query) {
                                        $q->where('payee', 'like', "%{$query}%")
                                            ->orWhere('description', 'like', "%{$query}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (SimpleFinTransaction $txn) {
                                        $label = $txn->posted?->format('Y-m-d') . ' • ' . number_format((float)$txn->amount, 2) . ' • ' . ($txn->payee ?? '-') . ' • ' . mb_strimwidth($txn->description, 0, 50, '…');
                                        return [$txn->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $txn = SimpleFinTransaction::find($value);
                                return $txn
                                    ? ($txn->posted?->format('Y-m-d') . ' • ' . number_format((float)$txn->amount, 2) . ' • ' . ($txn->payee ?? '-') . ' • ' . mb_strimwidth($txn->description, 0, 50, '…'))
                                    : $value;
                            })
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $ids = $data['transactions'] ?? [];
                        if (! empty($ids)) {
                            SimpleFinTransaction::whereIn('id', $ids)->get()->each(function (SimpleFinTransaction $txn) use ($owner) {
                                $txn->spend()->associate($owner);
                                $txn->save();
                            });
                        }
                    }),
            ])
            ->recordActions([
                // Nothing custom for now
            ])
            ->bulkActions([
                //
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderByDesc('is_pending')
                ->orderByDesc('posted')
            );
    }
}
