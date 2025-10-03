<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Pages\CreateActivity;
use App\Filament\Resources\ActivityResource\Pages\EditActivity;
use App\Filament\Resources\ActivityResource\RelationManagers\RedemptionsRelationManager;
use App\Filament\Resources\ActivityResource\RelationManagers\SpendsRelationManager;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activities';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),
            DateRangePicker::make('start_end_date')
                ->alwaysShowCalendar()
                ->required(),
            Grid::make(1)->schema([
                Textarea::make('description')
                    ->rows(5),
            ]),

            //            DatePicker::make('start_date'),
            //
            //            DatePicker::make('end_date'),
        ]);
    }

    public static function splitStartEndDate(array $data): array
    {
        [$data['start_date'], $data['end_date']] = explode(' - ', $data['start_end_date']);
        unset($data['start_end_date']);
        $data['start_date'] = Carbon::createFromFormat('d/m/Y', $data['start_date'])->toDateString();
        $data['end_date'] = Carbon::createFromFormat('d/m/Y', $data['end_date'])->toDateString();

        return $data;
    }

    public static function combineStartEndDate(array $data): array
    {
        $start_date = Carbon::parse($data['start_date'])->format('d/m/Y');
        $end_date = Carbon::parse($data['end_date'])->format('d/m/Y');
        $data['start_end_date'] = $start_date.' - '.$end_date;

        return $data;
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

            TextColumn::make('normalized_total_spend')
                ->label('Spend per day')
                ->money('USD'),

            TextColumn::make('start_date')
                ->date()
                ->sortable(),

            TextColumn::make('end_date')
                ->date(),
        ])
            ->defaultPaginationPageOption('all')
            ->persistSortInSession()
            ->defaultPaginationPageOption('all')
            ->defaultSort('start_date')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                TernaryFilter::make('archived')
                    ->label('Archived')
                    ->placeholder('All')
                    ->trueLabel('Archived Only')
                    ->falseLabel('Not Archived')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->where('end_date', '<', Carbon::now()->subDays(Activity::ARCHIVE_DAY_GRACE)),
                        false: fn (Builder $query) => $query->where('end_date', '>=', Carbon::now()->subDays(Activity::ARCHIVE_DAY_GRACE)),
                        blank: fn (Builder $query) => $query, // we do not want to filter the query when it is blank.
                    ),
            ])
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'create' => CreateActivity::route('/create'),
            'edit' => EditActivity::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SpendsRelationManager::class,
            RedemptionsRelationManager::class
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
