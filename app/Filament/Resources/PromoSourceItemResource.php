<?php

namespace App\Filament\Resources;

use App\Enums\PromoStatus;
use App\Filament\Resources\PromoSourceItemResource\Pages\EditPromoSourceItem;
use App\Filament\Resources\PromoSourceItemResource\Pages\ListPromoSourceItems;
use App\Models\PromoSourceItem;
use App\Services\TravelWallet\PromoIngestor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PromoSourceItemResource extends Resource
{
    protected static ?string $model = PromoSourceItem::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Travel wallet';

    protected static ?string $navigationLabel = 'Promo review';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->columnSpanFull(),
            Select::make('status')
                ->options(PromoStatus::asSelectArray())
                ->required(),
            TextInput::make('source')->disabledOn('edit'),
            TextInput::make('external_id')->disabledOn('edit'),
            KeyValue::make('facts')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->wrap()->limit(80),
                TextColumn::make('source'),
                TextColumn::make('status')->badge(),
                TextColumn::make('fetched_at')->dateTime()->since(),
            ])
            ->defaultSort('fetched_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PromoStatus::asSelectArray())
                    ->default(PromoStatus::PendingReview),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (PromoSourceItem $record): bool => $record->status->is(PromoStatus::PendingReview))
                    ->successNotificationTitle('Promo approved')
                    ->after(function (HasTable $livewire): void {
                        $livewire->resetTable();
                    })
                    ->action(function (PromoSourceItem $record): void {
                        app(PromoIngestor::class)->approve($record);
                    }),
                Action::make('reject')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PromoSourceItem $record): bool => $record->status->is(PromoStatus::PendingReview))
                    ->successNotificationTitle('Promo rejected')
                    ->after(function (HasTable $livewire): void {
                        $livewire->resetTable();
                    })
                    ->action(function (PromoSourceItem $record): void {
                        app(PromoIngestor::class)->reject($record);
                    }),
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
            'index' => ListPromoSourceItems::route('/'),
            'edit' => EditPromoSourceItem::route('/{record}/edit'),
        ];
    }
}
