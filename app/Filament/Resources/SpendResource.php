<?php

namespace App\Filament\Resources;

use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use App\Filament\Resources\SpendResource\Pages\ListSpends;
use App\Filament\Resources\SpendResource\Pages\CreateSpend;
use App\Filament\Resources\SpendResource\Pages\EditSpend;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Filament\Resources;
use App\Models\Card;
use App\Models\Spend;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use App\Filament\Resources\SpendResource\RelationManagers\TransactionsRelationManager as SpendTransactionsRelationManager;

class SpendResource extends Resource
{
    protected static ?string $model = Spend::class;

    protected static ?string $slug = 'spends';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationParentItem = 'Activities';

    public static function formSchema($includeActivity = false): array
    {
        $activity = $includeActivity
            ? [
                Select::make('activity_id')
                    ->relationship('activity', 'name'),
            ]
            : [
                // intentionally blank
            ];

        return [
            ...$activity,
            Flex::make([
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
            ])->columnSpanFull(),
//            Grid::make(1)->schema([
                Spend::paymentFilamentFormComponent()
                    ->columnSpanFull()
                    ->grid(['xs' => 1, 'md' => 2]),
//            ]),
        ];
    }

    public static function tableSchema($includeActivity = false)
    {
        $activity = $includeActivity
            ? [
                TextColumn::make('activity.name'),
            ] : [
                // intentionally blank
            ];

        return [
            ...$activity,
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
                        function (Builder $query) {
                            $copy = $query->clone();

                            return $query
                                ->where('spends.is_income', false)
                                ->where('payments.spend_type', getMorphAliasForClass(Spend::class))
                                ->join('payments', 'spends.id', 'payments.spend_id')
                                ->sum('payments.amount')
                                - $copy
                                    ->where('spends.is_income', true)
                                    ->where('payments.spend_type', getMorphAliasForClass(Spend::class))
                                    ->join('payments', 'spends.id', 'payments.spend_id')
                                    ->sum('payments.amount');
                        }
                    )
                ),
            TextColumn::make('type')
                ->action(EditAction::make()),

            TextColumn::make('subtype')
                ->action(EditAction::make()),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formSchema(true));
    }

    public static function table(Table $table): Table
    {
        return $table->columns(self::tableSchema(true))
            ->headerActions([
                CreateAction::make()->schema(self::formSchema(true)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpends::route('/'),
            'create' => CreateSpend::route('/create'),
            'edit' => EditSpend::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SpendTransactionsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
