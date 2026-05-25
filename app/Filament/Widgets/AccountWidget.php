<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasMathInputColumn;
use App\Models\Account;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class AccountWidget extends BaseWidget
{
    use HasMathInputColumn;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Accounts';

    public function table(Table $table): Table
    {
        return $table->query(Account::query())
            ->paginated(false)
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->description(fn (Model $record) => $record->updated_at->shortRelativeDiffForHumans(),
                            'below'),
                    $this->mathInputColumn('balance')
                        ->summarize(
                            Summarizer::make()
                                ->money('USD')
                                ->label('Total (USD)')
                                ->using(fn (QueryBuilder $query): float => Account::sumBalanceInUsd($query))
                        ),
                ]),
            ])->contentGrid(fn () => $this->gridSize());
    }

    public function gridSize(): array
    {
        return [
            'xs' => 2,
            'sm' => 2,
            'md' => 4,
            'lg' => 4,
            'xl' => 4,
        ];
    }
}
