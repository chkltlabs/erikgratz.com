<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use App\Filament\Forms\Components\LocationPicker;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\SpendResource;
use App\Models\Activity;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class SpendsRelationManager extends RelationManager
{
    protected static string $relationship = 'spends';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(SpendResource::formSchema());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(SpendResource::tableSchema())
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('break_out')
                        ->label('Break out to new activity')
                        ->icon('heroicon-o-plus-circle')
                        ->form([
                            TextInput::make('name')
                                ->required()
                                ->live(onBlur: true),
                            DateRangePicker::make('start_end_date')
                                ->alwaysShowCalendar()
                                ->required(),
                            ...LocationPicker::make(),
                            Textarea::make('description')
                                ->rows(5),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $activityData = ActivityResource::splitStartEndDate($data);
                            $newActivity = Activity::create($activityData);

                            $records->each(function ($spend) use ($newActivity) {
                                $spend->update(['activity_id' => $newActivity->id]);
                            });

                            Notification::make()
                                ->title('Spends broken out into new activity: '.$newActivity->name)
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
