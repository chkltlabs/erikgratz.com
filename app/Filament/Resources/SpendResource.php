<?php

namespace App\Filament\Resources;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Filament\Resources;
use App\Models\Card;
use App\Models\Spend;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SpendResource extends Resource
{
    protected static ?string $model = Spend::class;

    protected static ?string $slug = 'spends';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema($includeActivity = false): array
    {
        $activity = $includeActivity
            ? [
                Select::make('activity_id')
                    ->relationship('activity', 'name'),
            ]
            : [
                //intentionally blank
            ];

        return [
            ...$activity,
            Grid::make(4)->schema([
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
        ];
    }

    public static function tableSchema($includeActivity = false)
    {
        $activity = $includeActivity
            ? [
                TextColumn::make('activity.name'),
            ]
            : [
                //intentionally blank
            ];

        return [
            ...$activity,
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->action(EditAction::make()),

            TextColumn::make('amount')
                ->color(fn (Model $record) => $record->is_income ? 'success' : 'danger')
                ->action(EditAction::make()),

            TextColumn::make('type')
                ->action(EditAction::make()),

            TextColumn::make('subtype')
                ->action(EditAction::make()),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::formSchema(true));
    }

    public static function table(Table $table): Table
    {
        return $table->columns(self::tableSchema(true))
            ->headerActions([
                CreateAction::make()->form(self::formSchema(true)),
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
