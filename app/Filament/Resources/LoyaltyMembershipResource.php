<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyMembershipResource\Pages\CreateLoyaltyMembership;
use App\Filament\Resources\LoyaltyMembershipResource\Pages\EditLoyaltyMembership;
use App\Filament\Resources\LoyaltyMembershipResource\Pages\ListLoyaltyMemberships;
use App\Filament\Resources\LoyaltyMembershipResource\RelationManagers\InheritedPerksRelationManager;
use App\Filament\Resources\LoyaltyMembershipResource\RelationManagers\PerksRelationManager;
use App\Models\LoyaltyMembership;
use App\Models\User;
use App\Rules\ValidMathExpression;
use App\Support\MathExpression;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class LoyaltyMembershipResource extends Resource
{
    protected static ?string $model = LoyaltyMembership::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Travel wallet';

    protected static ?string $navigationLabel = 'Loyalty';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Member')
                ->options(
                    User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->required()
                ->rules([
                    fn (Get $get, ?LoyaltyMembership $record): Unique => Rule::unique('loyalty_memberships', 'user_id')
                        ->where('loyalty_program_id', $get('loyalty_program_id'))
                        ->ignore($record),
                ]),
            Select::make('loyalty_program_id')
                ->label('Program')
                ->relationship('program', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('tier')
                ->maxLength(64),
            TextInput::make('loyalty_number')
                ->label('Loyalty number')
                ->maxLength(64),
            Select::make('conferred_by_card_id')
                ->label('Conferred by card')
                ->relationship('conferredByCard', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            TextInput::make('points_balance')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('program.name')->searchable(),
                TextColumn::make('program.kind')->badge(),
                TextColumn::make('tier'),
                TextColumn::make('loyalty_number')
                    ->label('Loyalty number')
                    ->searchable()
                    ->copyable(fn (?string $state): bool => filled($state))
                    ->copyMessage('Loyalty number copied')
                    ->placeholder('—'),
                TextColumn::make('conferredByCard.name')->label('Card'),
                TextInputColumn::make('points_balance')
                    ->label('Points')
                    ->rules([new ValidMathExpression])
                    ->updateStateUsing(function (LoyaltyMembership $record, mixed $state): int {
                        $value = (int) round(MathExpression::resolve($state));
                        $record->update(['points_balance' => $value]);

                        return $value;
                    }),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->label('Member')
                    ->relationship('user', 'name'),
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

    public static function getRelations(): array
    {
        return [
            InheritedPerksRelationManager::class,
            PerksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoyaltyMemberships::route('/'),
            'create' => CreateLoyaltyMembership::route('/create'),
            'edit' => EditLoyaltyMembership::route('/{record}/edit'),
        ];
    }
}
