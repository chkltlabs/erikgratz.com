<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BenefitResetAnchor;
use App\Enums\ResetPeriod;
use App\Models\CardBenefit;
use Illuminate\Support\Carbon;

class BenefitWindowCalculator
{
    /**
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    public function window(CardBenefit $benefit, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $period = $benefit->reset_period?->value ?? ResetPeriod::NoReset;

        if ($period === ResetPeriod::NoReset) {
            return ['start' => null, 'end' => null];
        }

        return match ($period) {
            ResetPeriod::Daily => [
                'start' => $asOf->copy(),
                'end' => $asOf->copy()->addDay(),
            ],
            ResetPeriod::Weekly => [
                'start' => $asOf->copy()->startOfWeek(),
                'end' => $asOf->copy()->startOfWeek()->addWeek(),
            ],
            ResetPeriod::Monthly => $this->periodicWindow($benefit, $asOf, 1),
            ResetPeriod::Quarterly => $this->periodicWindow($benefit, $asOf, 3),
            ResetPeriod::SemiAnnual => $this->periodicWindow($benefit, $asOf, 6),
            ResetPeriod::CalendarYearly, ResetPeriod::RenewalYearly => $this->periodicWindow($benefit, $asOf, 12),
            default => ['start' => null, 'end' => null],
        };
    }

    public function nextRefreshAt(CardBenefit $benefit, ?Carbon $asOf = null): ?Carbon
    {
        $window = $this->window($benefit, $asOf);

        return $window['end'];
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function periodicWindow(CardBenefit $benefit, Carbon $asOf, int $months): array
    {
        $start = $this->firstResetInYear($benefit, $asOf, $months)->startOfDay();

        while ($start->gt($asOf)) {
            $start = $start->copy()->subMonthsNoOverflow($months)->startOfDay();
        }

        while ($start->copy()->addMonthsNoOverflow($months)->lte($asOf)) {
            $start = $start->copy()->addMonthsNoOverflow($months)->startOfDay();
        }

        return [
            'start' => $start,
            'end' => $start->copy()->addMonthsNoOverflow($months)->startOfDay(),
        ];
    }

    private function firstResetInYear(CardBenefit $benefit, Carbon $asOf, int $months): Carbon
    {
        $anchor = $benefit->reset_anchor?->value ?? BenefitResetAnchor::Calendar;
        $card = $benefit->card;

        if ($months === 1) {
            $day = 1;

            if ($anchor === BenefitResetAnchor::Statement && $card?->statement_date) {
                $day = max(1, (int) $card->statement_date);
            } elseif ($anchor === BenefitResetAnchor::CardAnniversary && $card?->date_opened) {
                $day = max(1, (int) Carbon::parse($card->date_opened)->day);
            } elseif ($anchor === BenefitResetAnchor::Custom && $benefit->custom_reset_on) {
                $day = max(1, (int) $benefit->custom_reset_on->day);
            }

            return $this->dateOnMonthDay($asOf, 1, $day);
        }

        if ($anchor === BenefitResetAnchor::Custom && $benefit->custom_reset_on) {
            return $this->dateOnMonthDay(
                $asOf,
                (int) $benefit->custom_reset_on->month,
                (int) $benefit->custom_reset_on->day,
            );
        }

        if ($anchor !== BenefitResetAnchor::Calendar && $card?->date_opened) {
            $opened = Carbon::parse($card->date_opened);

            return $this->dateOnMonthDay($asOf, (int) $opened->month, (int) $opened->day);
        }

        return $asOf->copy()->month(1)->day(1);
    }

    private function dateOnMonthDay(Carbon $asOf, int $month, int $day): Carbon
    {
        $daysInMonth = $asOf->copy()->month($month)->daysInMonth;

        return $asOf->copy()->month($month)->day(min($day, $daysInMonth));
    }
}
