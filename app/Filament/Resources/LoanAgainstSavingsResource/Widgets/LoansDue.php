<?php

namespace App\Filament\Resources\LoanAgainstSavingsResource\Widgets;

use App\Models\LoanAgainstSavings;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LoansDue extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                LoanAgainstSavings::query()
            )
            ->columns([
                Tables\Columns\TextColumn::make('reason'),
                Tables\Columns\TextColumn::make('balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_paid')
            ])
            ->paginated(false)
            ;
    }
}
