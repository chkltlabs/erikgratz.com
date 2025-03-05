<?php

namespace App\Filament\Widgets;

use App\Enums\Period;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class SpendsThisMonth extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (Builder $query) => Payment::query()
                    ->whereHasMorph(
                        'spend',
                        PeriodicSpend::class,
                        fn ($q) => $q
                            ->where('period', Period::Monthly)
                    )
                    ->orWhereHasMorph(
                        'spend',
                        [Spend::class, PeriodicSpend::class],
                        fn ($q) => $q
                        ->whereMonth('paid_on', now()->month)
                        ->whereYear('paid_on', now()->year)
                    )
                    ->orderBy('paid_on')
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('spend.activity.name'),
                TextColumn::make('spend.activity.start_date')
                    ->date()
                    ->label("Start Date"),
                TextColumn::make('spend.activity.end_date')
                    ->date()
                    ->label("End Date"),
                TextColumn::make('spend.name'),
                TextColumn::make('amount')->money()
                    ->summarize([
                        'unpaid' => Sum::make('unpaid')
                            ->label('Unpaid')
                            ->money()
                            ->query(fn (Builder $query) =>
                                $query->where('is_paid', false)
                            ),
//                        'total' => Sum::make('total')->label('Total')->money()
                    ]),
                IconColumn::make('is_paid')
//                    ->updateStateUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => dd($query->toSql()))
                ,
                TextColumn::make('paid_on')
                    ->since()
                    ->dateTooltip(),
                TextColumn::make('card.name')
                    ->label('Card'),
            ]);
    }
}
