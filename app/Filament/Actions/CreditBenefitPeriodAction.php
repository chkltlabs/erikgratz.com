<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Enums\BenefitTrackingMode;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Support\Icons\Heroicon;

class CreditBenefitPeriodAction extends BenefitMutationAction
{
    public static function getDefaultName(): ?string
    {
        return 'creditPeriod';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Credit this period')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('gray')
            ->visible(fn (?CardBenefit $record): bool => $record?->tracking_mode?->is(BenefitTrackingMode::Auto) ?? false)
            ->successNotificationTitle('Period credited')
            ->action(function (CardBenefit $record, BenefitUsageRecorder $recorder): void {
                $recorder->creditThisPeriod($record);
            });
    }
}
