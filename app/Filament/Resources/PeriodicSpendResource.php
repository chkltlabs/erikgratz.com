<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PeriodicSpendResource\Pages\ListPeriodicSpends;
use App\Filament\Resources\PeriodicSpendResource\Pages\CreatePeriodicSpend;
use App\Filament\Resources\PeriodicSpendResource\Pages\EditPeriodicSpend;
use App\Enums\Period;
use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Filament\Resources\PeriodicSpendResource\Pages;
use App\Models\Card;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class PeriodicSpendResource extends Resource
{
    protected static ?string $model = PeriodicSpend::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Select::make('period')
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
                    PeriodicSpend::paymentFilamentFormComponent(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->searchable()
                    ->sortable(),
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
                            fn (\Illuminate\Database\Query\Builder $query) => (clone $query)
                                ->where('periodic_spends.is_income', false)
                                ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                ->where('periodic_spends.period', Period::Yearly)
                                ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                ->sum('payments.amount')
                                + ((clone $query)
                                    ->where('periodic_spends.is_income', false)
                                    ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                    ->where('periodic_spends.period', Period::Monthly)
                                    ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                    ->sum('payments.amount') * 12)
                                + ((clone $query)
                                    ->where('periodic_spends.is_income', false)
                                    ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                    ->where('periodic_spends.period', Period::Weekly)
                                    ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                    ->sum('payments.amount') * 52)
                                + ((clone $query)
                                    ->where('periodic_spends.is_income', false)
                                    ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                    ->where('periodic_spends.period', Period::Daily)
                                    ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                    ->sum('payments.amount') * (now()->isLeapYear() ? 366 : 365))
                                - (
                                    (clone $query)
                                        ->where('periodic_spends.is_income', true)
                                        ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                        ->where('periodic_spends.period', Period::Yearly)
                                        ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                        ->sum('payments.amount')
                                    + ((clone $query)
                                        ->where('periodic_spends.is_income', true)
                                        ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                        ->where('periodic_spends.period', Period::Monthly)
                                        ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                        ->sum('payments.amount') * 12)
                                    + ((clone $query)
                                        ->where('periodic_spends.is_income', true)
                                        ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                        ->where('periodic_spends.period', Period::Weekly)
                                        ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                        ->sum('payments.amount') * 52)
                                    + ((clone $query)
                                        ->where('periodic_spends.is_income', true)
                                        ->where('payments.spend_type', getMorphAliasForClass(PeriodicSpend::class))
                                        ->where('periodic_spends.period', Period::Daily)
                                        ->join('payments', 'periodic_spends.id', 'payments.spend_id')
                                        ->sum('payments.amount') * (now()->isLeapYear() ? 366 : 365))
                                )
                        )
                    ),
                TextColumn::make('type')
                    ->sortable(),

                TextColumn::make('subtype')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->options(Period::asSelectArray()),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => ActivityResource::combineStartEndDate($data))
                    ->mutateDataUsing(fn (array $data): array => ActivityResource::splitStartEndDate($data)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated(false);
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
            'index' => ListPeriodicSpends::route('/'),
            'create' => CreatePeriodicSpend::route('/create'),
            'edit' => EditPeriodicSpend::route('/{record}/edit'),
        ];
    }
}
