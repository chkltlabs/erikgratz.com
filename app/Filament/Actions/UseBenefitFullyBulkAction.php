<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Enums\BenefitTrackingMode;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class UseBenefitFullyBulkAction extends BenefitMutationBulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'useFully';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Use fully')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Mark selected benefits as used?')
            ->successNotificationTitle('Selected benefits marked used')
            ->action(function (Collection $records, BenefitUsageRecorder $recorder): void {
                $records->each(function (CardBenefit $record) use ($recorder): void {
                    if ($record->tracking_mode->is(BenefitTrackingMode::Auto)) {
                        $recorder->assumeFully($record);

                        return;
                    }

                    $recorder->useFully($record);
                });
            });
    }
}
