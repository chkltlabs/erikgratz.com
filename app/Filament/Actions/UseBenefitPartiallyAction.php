<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Enums\BenefitTrackingMode;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitUsageRecorder;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class UseBenefitPartiallyAction extends BenefitMutationAction
{
    public static function getDefaultName(): ?string
    {
        return 'usePartial';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Use partial')
            ->icon(Heroicon::OutlinedMinus)
            ->form([
                TextInput::make('units')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->label('Amount or quantity'),
            ])
            ->successNotificationTitle('Usage recorded')
            ->action(function (CardBenefit $record, array $data, BenefitUsageRecorder $recorder): void {
                $units = (float) $data['units'];

                if ($record->tracking_mode->is(BenefitTrackingMode::Auto)) {
                    $recorder->assumePartially($record, $units);

                    return;
                }

                $recorder->record($record, $units);
            });
    }
}
