<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers;

use App\Filament\Forms\Fields\SpendAssociationField;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\Spend;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
                //
            ])
            ->recordActions([
                ViewAction::make(),
                \Filament\Actions\Action::make('assign')
                    ->label('Assign to Spend')
                    ->icon('heroicon-o-link')
                    ->schema([
                        SpendAssociationField::make('spend', true),
                    ])
                    ->fillForm(fn (SimpleFinTransaction $record): array => [
                        'spend_type' => $record->spend_type,
                        'spend_id' => $record->spend_id,
                    ])
                    ->action(function (SimpleFinTransaction $record, array $data) {
                        $record->update($data);
                    })
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
