<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasMathInputColumn;
use App\Models\Account;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

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
                        ->summarize(Sum::make()->money()->label('')),
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
