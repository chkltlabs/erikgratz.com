<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Support\Icons\Heroicon;

class IgnoreBenefitAction extends BenefitMutationAction
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
            ->modalHeading('Stop tracking this benefit?')
            ->successNotificationTitle('Benefit ignored')
            ->action(function (CardBenefit $record, BenefitUsageRecorder $recorder): void {
                $recorder->ignore($record);
            });
    }
}
