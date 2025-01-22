<?php

namespace App\Filament\Resources;

use App\Enums\Period;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Filament\Resources\PeriodicSpendResource\Pages;
use App\Filament\Resources\PeriodicSpendResource\RelationManagers;
use App\Models\Card;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class PeriodicSpendResource extends Resource
{
    protected static ?string $model = PeriodicSpend::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                Forms\Components\Select::make('period')
                    ->options(fn () => Period::asSelectArray())
                    ->required(),

                DateRangePicker::make('start_end_date')
                    ->alwaysShowCalendar()
                    ->required(),

                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options(SpendType::asSelectArray())
                    ->afterStateUpdated(
                        function (?string $state, $get, $set) {
                            if (! in_array($get('subtype'),
                                SpendSubtype::getFilteredSet($state))) {
                                $set('subtype', null);
                            }
                        })
                    ->reactive()
                    ->required(),

                Select::make('subtype')
                    ->options(
                        fn ($get) => SpendSubtype::getFilteredSet($get('type'))
                    )
                    ->reactive()
                    ->required(),
                Toggle::make('is_income'),
                ]),
                Grid::make(1)->schema([
                    Repeater::make('payments')
                        ->columns(4)
                        ->relationship()
                        ->schema([
                            TextInput::make('amount')->numeric()->required(),
                            Toggle::make('is_paid'),
                            DatePicker::make('paid_on')->nullable(),
                            Select::make('card_id')
                                ->label('Card')
                                ->options(Card::all()->pluck('name', 'id'))
                                ->nullable(),
                        ])->defaultItems(0)->addActionLabel('Add Payment'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->action(EditAction::make()),

                TextColumn::make('amount')
                    ->money('USD')
                    ->color(fn (Model $record) => $record->is_income ? 'success' : 'danger')
                    ->action(EditAction::make())
                    ->summarize(Summarizer::make()
                        ->money('USD')
                        ->using(
                            fn (\Illuminate\Database\Query\Builder $query) => $query
                                ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                ->sum('payments.amount')
                        )
                    ),
                TextColumn::make('type')
                    ->action(EditAction::make()),

                TextColumn::make('subtype')
                    ->action(EditAction::make()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period')
                    ->options(Period::asSelectArray())
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeriodicSpends::route('/'),
            'create' => Pages\CreatePeriodicSpend::route('/create'),
            'edit' => Pages\EditPeriodicSpend::route('/{record}/edit'),
        ];
    }
}
