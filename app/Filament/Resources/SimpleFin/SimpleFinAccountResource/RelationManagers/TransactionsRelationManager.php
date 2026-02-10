<?php

namespace App\Filament\Resources\SimpleFin\SimpleFinAccountResource\RelationManagers;

use App\Models\PeriodicSpend;
use App\Models\Spend;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type as MorphType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
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
                //
            ])
            ->recordActions([
                ViewAction::make(),
                \Filament\Actions\Action::make('assign')
                    ->label('Assign to Spend')
                    ->icon('heroicon-o-link')
                    ->schema([
                        MorphToSelect::make('spend')
                            ->label('Associate To')
                            ->types([
                                MorphType::make(Spend::class)
                                    ->titleAttribute('name')
                                    ->getOptionLabelUsing(fn (array $record): string => ($record['name']))
                                    ->getSearchResultsUsing(fn (string $search): array => Spend::query()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhereHas('activity', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                                        ->get()
                                        ->flatMap(fn (Spend $record) => [
                                            $record->id => (
                                                $record->activity?->name
                                                        ? ($record->activity->name . ' • ')
                                                        : ''
                                                )
                                                . $record->name
                                                . ($record->activity?->start_date
                                                    ? (' • ' . Carbon::parse($record->activity->start_date)->format('M jS') )
                                                    : '')
                                                . ($record->activity?->end_date
                                                    ? (' - ' . Carbon::parse($record->activity->end_date)->format('M jS') )
                                                    : '')

                                        ])
                                        ->toArray()
                                    )
//                                    ->getOptionLabelFromRecordUsing(fn (Spend $record) => ($record->activity?->name ? ($record->activity->name . ' • ') : '') . $record->name)

                                ,
                                MorphType::make(PeriodicSpend::class)
                                    ->titleAttribute('name'),
                            ])
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->spend()->associate($data['spend']);
                        $record->save();
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
