<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Enums\BenefitTrackingMode;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Support\Icons\Heroicon;

class UseBenefitFullyAction extends BenefitMutationAction
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
            ->modalHeading('Mark this benefit as used?')
            ->successNotificationTitle('Benefit marked used')
            ->action(function (CardBenefit $record, BenefitUsageRecorder $recorder): void {
                if ($record->tracking_mode->is(BenefitTrackingMode::Auto)) {
                    $recorder->assumeFully($record);

                    return;
                }

                $recorder->useFully($record);
            });
    }
}
