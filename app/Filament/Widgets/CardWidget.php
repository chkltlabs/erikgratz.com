<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CardResource;
use App\Models\Card;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class CardWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Cards';
    public function table(Table $table): Table
    {
        return $table
            ->query(Card::query())
            ->paginated(false)
            ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->description(fn (Model $record)
                        => $record->updated_at->shortRelativeDiffForHumans(),
                            'below'),
                Tables\Columns\TextInputColumn::make('balance')
                    ->rules(['numeric'])
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextInputColumn::make('pending')
                    ->rules(['numeric'])
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextInputColumn::make('interest_saving_balance')
                    ->rules(['numeric'])
                    ->label('ISB')
                    ->summarize(Sum::make()->money()->label('')),
                Tables\Columns\TextInputColumn::make('interest_free_balance')
                    ->rules(['numeric'])
                    ->label('0% Bal')
                    ->summarize(Sum::make()->money()->label('')),
            ])
            ;
    }
}
