<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\RelationManagers\SpendsRelationManager;
use App\Models\Activity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activities';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required(),

            TextInput::make('description'),

            DatePicker::make('start_date'),

            DatePicker::make('end_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable(),

            TextColumn::make('description')->limit(25),

            TextColumn::make('spent_upcoming')
                ->label('Spent / Upcoming')
                ->state(fn (Model $record) => '<span>$'
                    .$record->paid
                    .'</span> / <span class="text-danger-600">$'
                    .$record->unpaid
                    .'</span>')
                ->html(),

            TextColumn::make('total_spend')
                ->color(fn ($state) => $state < 0 ? 'success' : 'danger')
                ->money('USD'),

            TextColumn::make('start_date')
                ->date()
                ->sortable(),

            TextColumn::make('end_date')
                ->date(),
        ])
            ->persistSortInSession()
            ->defaultSort('start_date')
            ->filters([
                //
            ]);
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
