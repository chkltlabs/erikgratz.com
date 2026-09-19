<?php

namespace App\Filament\Resources\CardResource\Widgets;

use App\Models\Card;
use App\Services\TravelWallet\FeeRoiCalculator;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class CardFeeRoi extends StatsOverviewWidget
{
    public ?Card $record = null;

    #[On('benefit-usage-recorded')]
    public function refreshFromBenefitUsage(): void {}

    protected function getStats(): array
    {
        if ($this->record === null) {
            return [];
        }

        $roi = app(FeeRoiCalculator::class)->forCard($this->record);

        return [
            Stat::make('Benefit value this year', '$'.number_format($roi['captured'], 2))
                ->description($roi['year_start']->toFormattedDateString().' – '.$roi['year_end']->toFormattedDateString()),
            Stat::make('Annual fee', '$'.number_format($roi['fee'], 2)),
            Stat::make('Net vs fee', '$'.number_format($roi['net'], 2))
                ->color($roi['net'] >= 0 ? 'success' : 'danger'),
        ];
    }
}
