<?php

namespace App\Filament\Widgets;

use App\Models\Card;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Query\Builder;

class SpendsThisMonth extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (Builder $query) => Payment::query()
                    ->whereMonth('paid_on', now()->month)
                    ->whereYear('paid_on', now()->year)
            )
            ->columns([
                TextColumn::make('spend.activity.name'),
                TextColumn::make('spend.activity.start_date')->date()->label("Start Date"),
                TextColumn::make('spend.activity.end_date')->date()->label("End Date"),
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
                ToggleColumn::make('is_paid'),
                TextColumn::make('paid_on')->date(),
                TextColumn::make('card.name')
                    ->label('Card'),
            ]);
    }
}
