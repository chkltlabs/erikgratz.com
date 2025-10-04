<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use App\Models\Card;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class CardWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Cards';

    public function table(Table $table): Table
    {
        return $table
            ->query(Card::query()->orderBy('due_date'))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Model $record) => 'Due: '
                        .(now()->setDay($record->due_date)->isPast()
                            ? now()->addMonth()->setDay($record->due_date)->shortAbsoluteDiffForHumans()
                            : now()->setDay($record->due_date)->shortAbsoluteDiffForHumans()
                        )
                        .', Upd: '
                        .$record->updated_at->shortRelativeDiffForHumans(),
                        'below')
                    ->color(fn (Model $record) => match (true) {
                        $record->due_date == now()->day => 'success',
                        $record->due_date > now()->day => 'info',
                        default => 'default'

                    }),

                TextInputColumn::make('balance')
                    ->rules(['numeric'])
                    ->updateStateUsing(fn ($record, $state) => $record->update(['balance' => is_null($state) ? 0 : $state]))
                    ->summarize(Sum::make()->money()->label('')),
                TextInputColumn::make('pending')
                    ->rules(['numeric'])
                    ->updateStateUsing(fn ($record, $state) => $record->update(['pending' => is_null($state) ? 0 : $state]))
                    ->summarize(Sum::make()->money()->label('')),
                TextInputColumn::make('interest_saving_balance')
                    ->rules(['numeric'])
                    ->updateStateUsing(fn ($record, $state) => $record->update(['interest_saving_balance' => is_null($state) ? 0 : $state]))
                    ->label('ISB')
                    ->summarize([
                        Sum::make()->money()->label('Total'),
                        Sum::make()
                            ->query(fn (Builder $query) => $query->where('due_date', '>=', now()->day))
                            ->money()->label('Unpaid'),
                    ]),
                TextInputColumn::make('interest_free_balance')
                    ->label('0% Bal')
                    ->rules(['numeric'])
                    ->updateStateUsing(fn ($record, $state) => $record->update(['interest_free_balance' => is_null($state) ? 0 : $state]))
                    ->summarize(Sum::make()->money()->label('')),
                TextInputColumn::make('points_balance')
                    ->rules(['numeric'])
                    ->updateStateUsing(fn ($record, $state) => $record->update(['points_balance' => is_null($state) ? 0 : $state]))
                    ->label('Pts Bal')
                    ->summarize(Sum::make()->numeric()->label('')),
            ]);
    }
}
