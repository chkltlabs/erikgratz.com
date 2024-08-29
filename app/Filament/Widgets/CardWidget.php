<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CardResource;
use App\Models\Card;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CardWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Cards';
    public function table(Table $table): Table
    {
        return $table
            ->query(Card::query())
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextInputColumn::make('balance')
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextInputColumn::make('pending')
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextInputColumn::make('interest_saving_balance')
                    ->label('ISB')
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextInputColumn::make('interest_free_balance')
                    ->label('0% Bal')
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
            ])
            ->paginated(false)
            ;
    }
}
