<?php

namespace App\Filament\Resources;

use App\Filament\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers\SpendsRelationManager;
use App\Models\Activity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                ->content(fn(?Activity $record): string => $record?->created_at?->diffForHumans() ?? '-'),

            Placeholder::make('updated_at')
                ->label('Last Modified Date')
                ->content(fn(?Activity $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable(),

            TextColumn::make('description')->limit(25),

            TextColumn::make('total_spend'),

            TextColumn::make('start_date')
                ->date(),

            TextColumn::make('end_date')
                ->date(),
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
            SpendsRelationManager::class
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
