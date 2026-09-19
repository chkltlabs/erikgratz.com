<?php

namespace App\Models;

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Enums\BookingCabin;
use App\Enums\BookingChannel;
use App\Enums\ResetPeriod;
use App\Models\Traits\GetsDumped;
use App\Services\TravelWallet\BenefitWindowCalculator;
use App\Services\TravelWallet\BookingRequest;
use App\Services\TravelWallet\VendorMatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CardBenefit extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'card_id',
        'benefit',
        'description',
        'is_useable',
        'is_used',
        'value',
        'reset_period',
        'tracking_mode',
        'value_kind',
        'quantity_total',
        'reset_anchor',
        'custom_reset_on',
        'next_refresh_at',
        'applies_to',
        'location_country',
        'location_city',
        'loyalty_membership_id',
        'required_channel',
        'allowed_vendors',
        'max_apply_per_use',
        'award_only',
        'auto_assume_amount',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'is_useable' => 'boolean',
            'reset_period' => ResetPeriod::class,
            'tracking_mode' => BenefitTrackingMode::class,
            'value_kind' => BenefitValueKind::class,
            'reset_anchor' => BenefitResetAnchor::class,
            'applies_to' => BenefitAppliesTo::class,
            'required_channel' => BookingChannel::class,
            'custom_reset_on' => 'date',
            'next_refresh_at' => 'date',
            'value' => 'float',
            'allowed_vendors' => 'array',
            'max_apply_per_use' => 'array',
            'award_only' => 'boolean',
            'auto_assume_amount' => 'float',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function loyaltyMembership(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMembership::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(BenefitUsage::class);
    }

    public function perks(): HasMany
    {
        return $this->hasMany(BookingPerk::class);
    }

    public function allotment(): float
    {
        return match ($this->value_kind->value) {
            BenefitValueKind::Quantity => (float) ($this->quantity_total ?? 0),
            BenefitValueKind::Flag => 1.0,
            default => (float) ($this->value ?? 0),
        };
    }

    public function standingAssumeAmount(): float
    {
        if ($this->auto_assume_amount === null) {
            return $this->allotment();
        }

        return round(min((float) $this->auto_assume_amount, $this->allotment()), 2);
    }

    public function assumedCaptureLabel(): ?string
    {
        if (! $this->tracking_mode->is(BenefitTrackingMode::Auto)) {
            return null;
        }

        if ($this->auto_assume_amount === null) {
            return 'Assume full';
        }

        if ($this->value_kind->is(BenefitValueKind::Currency)) {
            return 'Assume $'.number_format((float) $this->auto_assume_amount, 2);
        }

        return 'Assume '.$this->auto_assume_amount;
    }

    /**
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    public function currentWindow(?Carbon $asOf = null): array
    {
        return app(BenefitWindowCalculator::class)->window($this, $asOf);
    }

    public function remaining(?Carbon $asOf = null): float
    {
        $window = $this->currentWindow($asOf);
        $used = $this->usagesInWindow($window['start'], $window['end']);

        return round(max(0, $this->allotment() - $used), 2);
    }

    public function formattedRemaining(?Carbon $asOf = null): string
    {
        $remaining = $this->remaining($asOf);

        return match ($this->value_kind->value) {
            BenefitValueKind::Currency => '$'.number_format($remaining, 2),
            BenefitValueKind::Quantity => (string) $remaining,
            default => $remaining > 0 ? 'Available' : 'Used',
        };
    }

    public function usagesInWindow(?Carbon $start, ?Carbon $end): float
    {
        $query = $this->usages();

        if ($start !== null) {
            $query->whereDate('used_on', '>=', $start->copy()->startOfDay());
        }

        if ($end !== null) {
            $query->whereDate('used_on', '<', $end->copy()->startOfDay());
        }

        if ($this->value_kind->is(BenefitValueKind::Currency)) {
            return (float) $query->sum('amount');
        }

        return (float) $query->sum('quantity');
    }

    public function isExhausted(?Carbon $asOf = null): bool
    {
        return $this->remaining($asOf) <= 0;
    }

    public function isLocationScoped(): bool
    {
        return filled($this->location_country) || filled($this->location_city);
    }

    public function matchesLocation(?string $country, ?string $city, ?string $locationName = null): bool
    {
        if (! $this->isLocationScoped()) {
            return true;
        }

        $haystack = strtolower(trim(implode(' ', array_filter([$country, $city, $locationName]))));

        if ($haystack === '') {
            return false;
        }

        if (filled($this->location_city) && str_contains($haystack, strtolower((string) $this->location_city))) {
            return true;
        }

        if (filled($this->location_country) && str_contains($haystack, strtolower((string) $this->location_country))) {
            return true;
        }

        return false;
    }

    public function appliesToCategory(string $category): bool
    {
        if ($this->applies_to->is(BenefitAppliesTo::Other)) {
            return false;
        }

        if ($this->applies_to->is(BenefitAppliesTo::Any)) {
            return true;
        }

        return $this->applies_to->value === $category;
    }

    public function isExpendableCredit(): bool
    {
        if ($this->value_kind->is(BenefitValueKind::Flag)) {
            return false;
        }

        if ($this->tracking_mode->is(BenefitTrackingMode::Ignore)) {
            return false;
        }

        return true;
    }

    public function matchesVendor(?string $vendor): bool
    {
        $allowed = array_values(array_filter(
            array_map(fn (mixed $needle): string => strtolower(trim((string) $needle)), $this->allowed_vendors ?? []),
            fn (string $needle): bool => $needle !== '',
        ));

        if ($allowed === []) {
            return true;
        }

        if (! filled($vendor)) {
            return false;
        }

        return app(VendorMatcher::class)->matchesAny($vendor, $allowed);
    }

    public function remainingUses(?Carbon $asOf = null): ?int
    {
        if ($this->quantity_total === null) {
            return null;
        }

        $window = $this->currentWindow($asOf);
        $used = $this->usesInWindow($window['start'], $window['end']);

        return max(0, (int) $this->quantity_total - $used);
    }

    public function maxApplyFor(?BookingCabin $cabin): ?float
    {
        $map = $this->max_apply_per_use ?? [];
        if ($map === []) {
            return null;
        }

        $key = $cabin?->value;
        $amount = $map[$key] ?? $map['default'] ?? null;

        if ($amount === null || $amount === '') {
            return null;
        }

        return (float) $amount;
    }

    public function applyableAmount(BookingRequest $request, ?float $price = null): float
    {
        if ($this->remainingUses() === 0) {
            return 0.0;
        }

        $remaining = $this->remaining();
        if ($remaining <= 0) {
            return 0.0;
        }

        $against = $price ?? $request->cashPrice;
        $cap = $this->maxApplyFor($request->cabin) ?? $remaining;

        if ($against === null) {
            return round(min($remaining, $cap), 2);
        }

        return round(min((float) $against, $remaining, $cap), 2);
    }

    public function usesInWindow(?Carbon $start, ?Carbon $end): int
    {
        $query = $this->usages();

        if ($start !== null) {
            $query->whereDate('used_on', '>=', $start->copy()->startOfDay());
        }

        if ($end !== null) {
            $query->whereDate('used_on', '<', $end->copy()->startOfDay());
        }

        return (int) $query->sum('quantity');
    }
}
