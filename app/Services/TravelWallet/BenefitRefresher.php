<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BenefitTrackingMode;
use App\Models\CardBenefit;
use Illuminate\Support\Carbon;

class BenefitRefresher
{
    public function __construct(
        private BenefitWindowCalculator $windows,
        private BenefitUsageRecorder $recorder,
    ) {}

    public function refreshAll(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? now();
        $updated = 0;

        CardBenefit::query()
            ->with(['card', 'usages'])
            ->each(function (CardBenefit $benefit) use ($asOf, &$updated): void {
                if ($this->refreshOne($benefit, $asOf)) {
                    $updated++;
                }
            });

        return $updated;
    }

    public function refreshOne(CardBenefit $benefit, ?Carbon $asOf = null): bool
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $next = $this->windows->nextRefreshAt($benefit, $asOf);
        $previousNext = $benefit->next_refresh_at
            ? $benefit->next_refresh_at->copy()->startOfDay()
            : null;

        if ($previousNext !== null && $previousNext->lte($asOf) && $benefit->tracking_mode->is(BenefitTrackingMode::Auto)) {
            $closingEnd = $previousNext;
            $closingWindow = $this->windows->window($benefit, $closingEnd->copy()->subDay());
            $used = $benefit->usagesInWindow($closingWindow['start'], $closingWindow['end']);

            if ($used <= 0 && $benefit->standingAssumeAmount() > 0) {
                $this->recorder->assumeAutoForWindow($benefit, $closingWindow['start'] ?? $closingEnd->copy()->subDay());
            }
        }

        $dirty = $benefit->next_refresh_at?->toDateString() !== $next?->toDateString();
        $exhausted = $benefit->isExhausted($asOf);

        if ($benefit->is_used !== $exhausted) {
            $benefit->is_used = $exhausted;
            $dirty = true;
        }

        if ($dirty) {
            $benefit->next_refresh_at = $next;
            $benefit->save();
        }

        return $dirty;
    }
}
