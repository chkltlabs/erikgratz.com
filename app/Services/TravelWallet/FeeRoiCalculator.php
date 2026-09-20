<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Models\BenefitUsage;
use App\Models\Card;
use Illuminate\Support\Carbon;

class FeeRoiCalculator
{
    /**
     * @return array{captured: float, fee: float, net: float, year_start: Carbon, year_end: Carbon}
     */
    public function forCard(Card $card, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        [$start, $end] = $this->membershipYear($card, $asOf);

        $captured = (float) BenefitUsage::query()
            ->whereHas('benefit', fn ($query) => $query->where('card_id', $card->id))
            ->whereDate('used_on', '>=', $start)
            ->whereDate('used_on', '<', $end)
            ->with('benefit')
            ->get()
            ->sum(fn (BenefitUsage $usage): float => $usage->capturedAmount());

        $fee = (float) ($card->annual_fee ?? 0);

        return [
            'captured' => round($captured, 2),
            'fee' => $fee,
            'net' => round($captured - $fee, 2),
            'year_start' => $start,
            'year_end' => $end,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function membershipYear(Card $card, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $opened = $card->date_opened
            ? Carbon::parse($card->date_opened)->startOfDay()
            : $asOf->copy()->month(1)->day(1);

        $start = $asOf->copy()
            ->month((int) $opened->month)
            ->day(min((int) $opened->day, $asOf->copy()->month((int) $opened->month)->daysInMonth));

        if ($start->gt($asOf)) {
            $start->subYear();
        }

        return [$start, $start->copy()->addYear()];
    }
}
