<?php

namespace App\Filament;

use App\Filament\SpendResource\Pages;
use App\Models\Spend;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;

class SpendResource extends Resource
{
    protected static ?string $model = Spend::class;

    protected static ?string $slug = 'spends';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema(): array
    {
        return [
            DatePicker::make('spend_on')
                ->label('Spend Month'),

            DatePicker::make('spend_at')
                ->label('Spend Date'),

            TextInput::make('name')
                ->required(),

            TextInput::make('amount')
                ->required()
                ->numeric(),

            Toggle::make('is_income'),

            TextInput::make('type')
                ->required(),

            TextInput::make('subtype'),

            Placeholder::make('created_at')
                ->label('Created Date')
                ->content(fn(?Spend $record): string => $record?->created_at?->diffForHumans() ?? '-'),

            Placeholder::make('updated_at')
                ->label('Last Modified Date')
                ->content(fn(?Spend $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
        ];
    }
    public static function form(Form $form): Form
    {
        return $form->schema(self::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('spend_on')
                ->date(),

            TextColumn::make('spend_at')
                ->label('Spend Date')
                ->date(),

            TextColumn::make('name')
                ->searchable()
                ->sortable(),

            TextColumn::make('amount'),

            TextColumn::make('type'),

            TextColumn::make('subtype'),

            TextColumn::make('month_on'),

            TextColumn::make('month_at'),
        ])->headerActions([
            CreateAction::make()->form(self::formSchema())
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpends::route('/'),
            'create' => Pages\CreateSpend::route('/create'),
            'edit' => Pages\EditSpend::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
