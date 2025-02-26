<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class AccountWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Accounts';
    public function table(Table $table): Table
    {
        return $table->query(Account::query())
            ->paginated(false)
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('name')
                        ->description(fn (Model $record)
                        => $record->updated_at->shortRelativeDiffForHumans(),
                            'below'),
                    Tables\Columns\TextInputColumn::make('balance')
                        ->rules(['numeric'])
                        ->summarize(Sum::make()->money()->label(''))
//                        ->sortable()
                ])
            ])->contentGrid(fn () => [
                'xs' => 3,
                'sm' => 3,
                'md' => 6,
                'lg' => 6,
                'xl' => 6,
            ])
            ;
    }
}
