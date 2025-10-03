<?php

namespace App\Filament\Resources\LoanAgainstSavingsResource\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                TextColumn::make('reason'),
                TextColumn::make('balance')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('paid_on')
                    ->date()
                    ->sortable(),
                ToggleColumn::make('is_paid')
            ])
            ->paginated(false)
            ;
    }
}
