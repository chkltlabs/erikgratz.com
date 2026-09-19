<?php

namespace App\Filament\Resources;

use App\Enums\PointsProgram;
use App\Filament\Resources\TransferRouteResource\Pages\CreateTransferRoute;
use App\Filament\Resources\TransferRouteResource\Pages\EditTransferRoute;
use App\Filament\Resources\TransferRouteResource\Pages\ListTransferRoutes;
use App\Models\TransferRoute;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TransferRouteResource extends Resource
{
    protected static ?string $model = TransferRoute::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Travel wallet';

    protected static ?string $navigationLabel = 'Transfer routes';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('from_program')
                ->options(PointsProgram::asSelectArray())
                ->required(),
            Select::make('loyalty_program_id')
                ->relationship('program', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('base_ratio')
                ->numeric()
                ->default(1)
                ->required(),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_program'),
                TextColumn::make('program.name')->label('Partner'),
                TextColumn::make('base_ratio'),
                IconColumn::make('is_active')->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => ListTransferRoutes::route('/'),
            'create' => CreateTransferRoute::route('/create'),
            'edit' => EditTransferRoute::route('/{record}/edit'),
        ];
    }
}
