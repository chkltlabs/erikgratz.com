<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CreditBenefitPeriodBulkAction extends BenefitMutationBulkAction
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
            ->requiresConfirmation()
            ->modalHeading('Credit selected benefits for this period?')
            ->successNotificationTitle('Selected periods credited')
            ->action(function (Collection $records, BenefitUsageRecorder $recorder): void {
                $records->each(fn (CardBenefit $record) => $recorder->creditThisPeriod($record));
            });
    }
}
