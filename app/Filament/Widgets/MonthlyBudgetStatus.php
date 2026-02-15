<?php

namespace App\Filament\Widgets;

use App\Models\PeriodicSpend;
use App\Models\Spend;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonthlyBudgetStatus extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Monthly Budget Status (Confirmed Transactions)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PeriodicSpend::query()
                    ->where('is_income', false)
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->startOfMonth());
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('total_spend')
                    ->label('Budget')
                    ->money(),
                Tables\Columns\TextColumn::make('actual_spend')
                    ->label('Actual')
                    ->money(),
                Tables\Columns\TextColumn::make('variance')
                    ->label('Variance')
                    ->money()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\ViewColumn::make('progress')
                    ->label('Progress')
                    ->view('filament.tables.columns.progress-bar'),
            ]);
    }
}
