<?php

namespace App\Filament\Resources;

use App\Filament\Resources;
use App\Models\Spend;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpendResource extends Resource
{
    protected static ?string $model = Spend::class;

    protected static ?string $slug = 'spends';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema(): array
    {
        return [
            TextInput::make('name')
                ->required(),

            TextInput::make('amount')
                ->required()
                ->numeric(),

            TextInput::make('type')
                ->required(),

            TextInput::make('subtype'),
            Toggle::make('is_income'),
            DatePicker::make('spend_for')
                ->label('Spend Month'),

            DatePicker::make('spend_at')
                ->label('Spend Date'),

            Placeholder::make('created_at')
                ->label('Created Date')
                ->content(fn (?Spend $record): string => $record?->created_at?->diffForHumans() ?? '-'),

            Placeholder::make('updated_at')
                ->label('Last Modified Date')
                ->content(fn (?Spend $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            //            TextColumn::make('spend_for')
            //                ->date(),
            //
            //            TextColumn::make('spend_at')
            //                ->label('Spend Date')
            //                ->date(),

            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->action(EditAction::make()),

            TextColumn::make('amount')->action(EditAction::make()),

            TextColumn::make('type')->action(EditAction::make()),

            TextColumn::make('subtype')->action(EditAction::make()),

            TextColumn::make('month_for')->description('The time period the spend is useful for'),

            TextColumn::make('month_at')->label('Month Spent'),
        ])->headerActions([
            CreateAction::make()->form(self::formSchema()),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Resources\SpendResource\Pages\ListSpends::route('/'),
            'create' => Resources\SpendResource\Pages\CreateSpend::route('/create'),
            'edit' => Resources\SpendResource\Pages\EditSpend::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
