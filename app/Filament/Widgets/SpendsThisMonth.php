<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SpendsThisMonth extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (Builder $query) => Payment::query()
                    ->where(fn ($q) => $q->monthly())
                    ->orWhere(fn ($qq) => $qq->yearlyDueThisMonth())
                    ->orWhere(fn ($qqq) => $qqq->oneTimeDueThisMonth())
                    ->orderByRaw('DATE_FORMAT(paid_on, "%d")')
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('spend.activity.name'),
                TextColumn::make('spend.name'),
                TextColumn::make('amount')->money()
                    ->summarize([
                        'unpaid' => Sum::make('unpaid')
                            ->label('Unpaid')
                            ->money()
                            ->query(fn (\Illuminate\Database\Query\Builder $query) => $query->whereRaw('DATE_FORMAT(paid_on, "%d") >= '.now()->day)
                            ),
                        //                        'total' => Sum::make('total')->label('Total')->money()
                    ]),
                IconColumn::make('is_paid')
                    ->state(fn (Model $record) => now()->day > $record->paid_on->day)
                    ->boolean(),
                TextColumn::make('paid_on')
                    ->dateTime('jS')
                    ->sinceTooltip(),
                TextColumn::make('card.name')
                    ->label('Card'),
            ]);
    }
}
