<?php

namespace App\Filament\Resources\CardResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Enums\ResetPeriod;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BenefitsRelationManager extends RelationManager
{
    protected static string $relationship = 'benefits';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('benefit')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_useable'),
                    Toggle::make('is_used'),
                ]),
                Grid::make(1)->schema([
                    Textarea::make('description')
                        ->maxLength(2048),
                ]),
                TextInput::make('value')
                    ->numeric(),
                Select::make('reset_period')
                    ->options(ResetPeriod::asSelectArray()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('benefit')
            ->columns([
                TextColumn::make('benefit')
                    ->tooltip(fn (Model $record): ?string => $record->description),
                IconColumn::make('is_useable'),
                ToggleColumn::make('is_used')
                    ->disabled(fn (?Model $record) => ! $record?->is_useable),
                TextColumn::make('value')->money(),
                TextColumn::make('reset_period'),
            ])
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
                ]),
            ]);
    }
}
