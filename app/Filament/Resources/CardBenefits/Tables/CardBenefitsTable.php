<?php

declare(strict_types=1);

namespace App\Filament\Resources\CardBenefits\Tables;

use App\Filament\Actions\CreditBenefitPeriodAction;
use App\Filament\Actions\CreditBenefitPeriodBulkAction;
use App\Filament\Actions\IgnoreBenefitAction;
use App\Filament\Actions\IgnoreBenefitBulkAction;
use App\Filament\Actions\UseBenefitFullyAction;
use App\Filament\Actions\UseBenefitFullyBulkAction;
use App\Filament\Actions\UseBenefitPartiallyAction;
use App\Filament\Resources\CardBenefits\Pages\ListCardBenefits;
use App\Filament\Tables\HidesIgnoredBenefits;
use App\Models\CardBenefit;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CardBenefitsTable
{
    public static function configure(Table $table): Table
    {
        return HidesIgnoredBenefits::configure($table)
            ->heading(fn (ListCardBenefits $livewire): string => $livewire->isAutoTab()
                ? 'Assumed captured when charged to the right card'
                : 'Unused benefits by expiry')
            ->description(fn (ListCardBenefits $livewire): ?string => $livewire->isAutoTab()
                ? 'Use fully, use partial, or ignore sets the standing assumption for every period until you change it. Credit this period is a one-off.'
                : null)
            ->columns([
                TextColumn::make('benefit')
                    ->description(fn (CardBenefit $record, ListCardBenefits $livewire): ?string => $livewire->isAutoTab()
                        ? $record->card?->name
                        : $record->description)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('card.name')
                    ->label('Card')
                    ->hidden(fn (ListCardBenefits $livewire): bool => $livewire->isAutoTab()),
                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->state(fn (CardBenefit $record): string => $record->formattedRemaining()),
                TextColumn::make('assumed_capture')
                    ->label('Assumption')
                    ->state(fn (CardBenefit $record): ?string => $record->assumedCaptureLabel())
                    ->visible(fn (ListCardBenefits $livewire): bool => $livewire->isAutoTab()),
                TextColumn::make('next_refresh_at')
                    ->label('Refreshes')
                    ->date()
                    ->placeholder('No reset')
                    ->sortable(),
                TextColumn::make('location')
                    ->hidden(fn (ListCardBenefits $livewire): bool => $livewire->isAutoTab())
                    ->state(fn (CardBenefit $record): string => trim(implode(', ', array_filter([
                        $record->location_city,
                        $record->location_country,
                    ]))) ?: 'Anywhere'),
            ])
            ->defaultSort('next_refresh_at')
            ->emptyStateHeading(fn (ListCardBenefits $livewire): string => $livewire->isAutoTab()
                ? 'No auto-captured benefits this period'
                : 'No unused benefits')
            ->emptyStateDescription(fn (ListCardBenefits $livewire): ?string => $livewire->isAutoTab()
                ? null
                : 'Everything tracked is used or ignored for this window.')
            ->emptyStateIcon(fn (ListCardBenefits $livewire): Heroicon => $livewire->isAutoTab()
                ? Heroicon::OutlinedBanknotes
                : Heroicon::OutlinedCheckCircle)
            ->recordActions([
                UseBenefitFullyAction::make(),
                UseBenefitPartiallyAction::make(),
                IgnoreBenefitAction::make(),
                CreditBenefitPeriodAction::make()
                    ->hidden(fn (ListCardBenefits $livewire): bool => ! $livewire->isAutoTab()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    UseBenefitFullyBulkAction::make(),
                    IgnoreBenefitBulkAction::make(),
                    CreditBenefitPeriodBulkAction::make()
                        ->hidden(fn (ListCardBenefits $livewire): bool => ! $livewire->isAutoTab()),
                ]),
            ])
            ->paginated(fn (ListCardBenefits $livewire): array|bool => $livewire->isAutoTab() ? false : [25, 50, 100]);
    }
}
