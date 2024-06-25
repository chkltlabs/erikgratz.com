<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\RelationManagers\SpendsRelationManager;
use App\Models\Activity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required(),

            TextInput::make('description'),

            DatePicker::make('start_date'),

            DatePicker::make('end_date'),

            Placeholder::make('created_at')
                ->label('Created Date')
                ->content(fn (?Activity $record): string => $record?->created_at?->diffForHumans() ?? '-'),

            Placeholder::make('updated_at')
                ->label('Last Modified Date')
                ->content(fn (?Activity $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable(),

            TextColumn::make('description')->limit(25),

            TextColumn::make('total_spend')
                ->color(fn ($state) => $state < 0 ? 'success' : 'danger'),

            TextColumn::make('start_date')
                ->date(),

            TextColumn::make('end_date')
                ->date(),
        ])->filters([
            Filter::make('date_range')
                ->form([
                    DatePicker::make('start_date')->default(now()->startOfYear()),
                    DatePicker::make('end_date')->default(now()->endOfYear()),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['start_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('end_date', '>=', $date),
                        )
                        ->when(
                            $data['end_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                        );
                }),
        ])->persistFiltersInSession()->deselectAllRecordsWhenFiltered();
    }

    public static function getPages(): array
    {
        return [
            'index' => ActivityResource\Pages\ListActivities::route('/'),
            'create' => ActivityResource\Pages\CreateActivity::route('/create'),
            'edit' => ActivityResource\Pages\EditActivity::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SpendsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
