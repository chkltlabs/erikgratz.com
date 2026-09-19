<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class IgnoreBenefitBulkAction extends BenefitMutationBulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'ignore';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ignore')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Stop tracking selected benefits?')
            ->successNotificationTitle('Selected benefits ignored')
            ->action(function (Collection $records, BenefitUsageRecorder $recorder): void {
                $records->each(fn (CardBenefit $record) => $recorder->ignore($record));
            });
    }
}
