<?php

declare(strict_types=1);

namespace App\Filament\Tables;

use App\Enums\BenefitTrackingMode;
use App\Models\CardBenefit;
use Closure;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HidesIgnoredBenefits
{
    public static function configure(Table $table, bool|Closure $filterVisible = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->orderByRaw('CASE WHEN tracking_mode = ? THEN 1 ELSE 0 END', [BenefitTrackingMode::Ignore]))
            ->recordClasses(fn (CardBenefit $record): ?string => $record->tracking_mode->is(BenefitTrackingMode::Ignore)
                ? 'opacity-50 text-gray-500'
                : null)
            ->filters([
                TernaryFilter::make('ignored')
                    ->label('Ignored')
                    ->placeholder('All')
                    ->trueLabel('Ignored only')
                    ->falseLabel('Hide ignored')
                    ->default(false)
                    ->visible($filterVisible)
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('tracking_mode', BenefitTrackingMode::Ignore),
                        false: fn (Builder $query): Builder => $query->where('tracking_mode', '!=', BenefitTrackingMode::Ignore),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ]);
    }
}
