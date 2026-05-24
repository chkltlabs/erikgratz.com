<?php

namespace App\Filament\Widgets;

use App\Models\Card;
use App\Rules\ValidMathExpression;
use App\Support\MathExpression;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
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

                $this->mathInputColumn('balance')
                    ->summarize(Sum::make()->money()->label('')),
                $this->mathInputColumn('pending')
                    ->summarize(Sum::make()->money()->label('')),
                $this->mathInputColumn('interest_saving_balance')
                    ->label('ISB')
                    ->summarize([
                        Sum::make()->money()->label('Total'),
                        Sum::make()
                            ->query(fn (Builder $query) => $query->where('due_date', '>=', now()->day))
                            ->money()->label('Unpaid'),
                    ]),
                $this->mathInputColumn('interest_free_balance')
                    ->label('0% Bal')
                    ->summarize(Sum::make()->money()->label('')),
                $this->mathInputColumn('points_balance', asInteger: true)
                    ->label('Pts Bal')
                    ->summarize(Sum::make()->numeric()->label('')),
            ]);
    }

    private function mathInputColumn(string $field, bool $asInteger = false): TextInputColumn
    {
        return TextInputColumn::make($field)
            ->rules([new ValidMathExpression])
            ->updateStateUsing(function ($record, $state) use ($field, $asInteger) {
                $resolved = MathExpression::resolve($state);
                $value = $asInteger ? (int) round($resolved) : $resolved;
                $record->update([$field => $value]);

                return $value;
            });
    }
}
