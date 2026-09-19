<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitUsageSource;
use App\Enums\BenefitValueKind;
use App\Models\BenefitUsage;
use App\Models\CardBenefit;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class BenefitUsageRecorder
{
    public function record(
        CardBenefit $benefit,
        float $units,
        ?Carbon $usedOn = null,
        ?BenefitUsageSource $source = null,
        ?int $activityId = null,
        ?int $paymentId = null,
        ?string $notes = null,
    ): BenefitUsage {
        if ($units <= 0) {
            throw new InvalidArgumentException('Usage must be greater than zero.');
        }

        $remaining = $benefit->remaining($usedOn);
        $units = min($units, $remaining);

        if ($units <= 0) {
            throw new InvalidArgumentException('No remaining value on this benefit in the current window.');
        }

        $usage = BenefitUsage::query()->create([
            'card_benefit_id' => $benefit->id,
            'used_on' => ($usedOn ?? now())->toDateString(),
            'source' => $source ?? BenefitUsageSource::Manual(),
            'activity_id' => $activityId,
            'payment_id' => $paymentId,
            'notes' => $notes,
            ...$this->unitsColumns($benefit, $units),
        ]);

        $benefit->unsetRelation('usages');
        $benefit->is_used = $benefit->isExhausted($usedOn);
        $benefit->save();

        return $usage;
    }

    public function useFully(CardBenefit $benefit, ?Carbon $usedOn = null, ?string $notes = null): ?BenefitUsage
    {
        $remaining = $benefit->remaining($usedOn);

        if ($remaining <= 0) {
            return null;
        }

        return $this->record($benefit, $remaining, $usedOn, notes: $notes);
    }

    public function ignore(CardBenefit $benefit): CardBenefit
    {
        $benefit->tracking_mode = BenefitTrackingMode::Ignore;
        $benefit->save();

        return $benefit;
    }

    public function assumeFully(CardBenefit $benefit, ?Carbon $usedOn = null): CardBenefit
    {
        $benefit->auto_assume_amount = null;
        $benefit->tracking_mode = BenefitTrackingMode::Auto;
        $benefit->save();
        $this->creditStandingIfUnused($benefit, $usedOn);

        return $benefit;
    }

    public function assumePartially(CardBenefit $benefit, float $amount, ?Carbon $usedOn = null): CardBenefit
    {
        $benefit->auto_assume_amount = $amount;
        $benefit->tracking_mode = BenefitTrackingMode::Auto;
        $benefit->save();
        $this->creditStandingIfUnused($benefit, $usedOn);

        return $benefit;
    }

    public function creditThisPeriod(CardBenefit $benefit, ?Carbon $usedOn = null): ?BenefitUsage
    {
        $units = min($benefit->standingAssumeAmount(), $benefit->remaining($usedOn));

        if ($units <= 0) {
            return null;
        }

        return $this->record($benefit, $units, $usedOn, BenefitUsageSource::AssumedAuto(), notes: 'Credited this period');
    }

    public function assumeAutoForWindow(CardBenefit $benefit, Carbon $usedOn): ?BenefitUsage
    {
        if ($benefit->tracking_mode->is(BenefitTrackingMode::Ignore)) {
            return null;
        }

        $units = $benefit->standingAssumeAmount();
        if ($units <= 0) {
            return null;
        }

        $usage = BenefitUsage::query()->create([
            'card_benefit_id' => $benefit->id,
            'used_on' => $usedOn->toDateString(),
            'source' => BenefitUsageSource::AssumedAuto,
            'notes' => 'Assumed captured when charged to this card',
            ...$this->unitsColumns($benefit, $units),
        ]);
        $benefit->unsetRelation('usages');

        return $usage;
    }

    private function creditStandingIfUnused(CardBenefit $benefit, ?Carbon $usedOn = null): ?BenefitUsage
    {
        $window = $benefit->currentWindow($usedOn);
        if ($benefit->usagesInWindow($window['start'], $window['end']) > 0) {
            return null;
        }

        return $this->creditThisPeriod($benefit, $usedOn);
    }

    /**
     * @return array{amount: ?float, quantity: ?int}
     */
    private function unitsColumns(CardBenefit $benefit, float $units): array
    {
        if ($benefit->value_kind->is(BenefitValueKind::Currency)) {
            return [
                'amount' => round($units, 2),
                'quantity' => $benefit->quantity_total !== null ? 1 : null,
            ];
        }

        return ['quantity' => (int) round($units), 'amount' => null];
    }
}
