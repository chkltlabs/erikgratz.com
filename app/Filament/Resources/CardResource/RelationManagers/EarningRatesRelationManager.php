<?php

namespace App\Filament\Resources\CardResource\RelationManagers;

use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EarningRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'earningRates';

    protected static ?string $title = 'Earning rates';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category')
                    ->options(BookingCategory::asSelectArray())
                    ->required(),
                Select::make('channel')
                    ->options(BookingChannel::asSelectArray())
                    ->required(),
                TextInput::make('vendor')
                    ->maxLength(64)
                    ->placeholder('Any'),
                TextInput::make('multiplier')
                    ->numeric()
                    ->required()
                    ->step(0.1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('multiplier')
            ->columns([
                TextColumn::make('category')->badge(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('vendor')->placeholder('Any'),
                TextColumn::make('multiplier')->suffix('x'),
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
