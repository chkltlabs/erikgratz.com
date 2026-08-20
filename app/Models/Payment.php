<?php

namespace App\Models;

use App\Enums\CurrencyCode;
use App\Enums\Period;
use App\Models\Traits\GetsDumped;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Payment extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = ['spend_id', 'spend_type', 'amount', 'currency', 'is_paid', 'paid_on', 'card_id'];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'is_paid' => 'boolean',
            'currency' => CurrencyCode::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Payment $payment): void {
            if ($payment->shouldSettleToUsd()) {
                $payment->settleToUsd();
            }
        });
    }

    public function spend(): MorphTo
    {
        return $this->morphTo();
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * USD amount for dashboards and aggregates. Paid foreign payments are stored in USD after settlement.
     * Unpaid foreign payments use the latest available rate.
     */
    public function amountInUsd(): float
    {
        $amount = (float) $this->amount;

        if ($this->currency->isUsd()) {
            return round($amount, 2);
        }

        if ($this->is_paid) {
            return round($amount, 2);
        }

        return app(ExchangeRateService::class)->convertToUsd($amount, $this->currency);
    }

    /**
     * Calendar date when this unpaid spend becomes CC cash-flow due.
     * Uses the card statement close → due cycle; null card floats +1 month from paid_on.
     */
    public function cashflowDueDate(): Carbon
    {
        $paidOn = $this->paid_on
            ? Carbon::parse($this->paid_on)->startOfDay()
            : now()->startOfDay();

        $card = $this->relationLoaded('card') ? $this->card : $this->card()->first();

        if ($card === null) {
            return $paidOn->copy()->addMonthNoOverflow();
        }

        return $card->cashflowDueDateForSpendDate($paidOn);
    }

    public function cashflowDueFallsInMonth(Carbon $month): bool
    {
        $due = $this->cashflowDueDate();

        return $due->month === $month->month && $due->year === $month->year;
    }

    public function shouldSettleToUsd(): bool
    {
        if ($this->currency->isUsd() || ! $this->is_paid) {
            return false;
        }

        return $this->wasRecentlyCreated
            || $this->wasChanged('is_paid')
            || ($this->wasChanged('paid_on') && $this->is_paid);
    }

    public function settleToUsd(): void
    {
        if ($this->currency->isUsd()) {
            return;
        }

        $settlementDate = $this->paid_on ?? now();
        $usdAmount = app(ExchangeRateService::class)->convertToUsd(
            (float) $this->amount,
            $this->currency,
            Carbon::parse($settlementDate),
        );

        $this->updateQuietly([
            'amount' => $usdAmount,
            'currency' => CurrencyCode::USD,
        ]);
    }

    public function scopeOneTimeDueThisMonth($query)
    {
        return $query
            ->whereMorphedTo('spend', Spend::class)
            ->whereMonth('paid_on', now()->month)
            ->whereYear('paid_on', now()->year);
    }

    public function scopeOneTimeDueNextMonth($query)
    {
        return $query
            ->whereMorphedTo('spend', Spend::class)
            ->whereMonth('paid_on', now()->addMonth()->month)
            ->whereYear('paid_on', now()->addMonth()->year);
    }

    public function scopeOneTimeUnpaidDueThisMonth($query)
    {
        return $query
            ->whereMorphedTo('spend', Spend::class)
            ->whereMonth('paid_on', now()->month)
            ->whereYear('paid_on', now()->year)
            ->where('is_paid', false);
    }

    public function scopeOneTimeUnpaidDueNextMonth($query)
    {
        return $query
            ->whereMorphedTo('spend', Spend::class)
            ->whereMonth('paid_on', now()->addMonth()->month)
            ->whereYear('paid_on', now()->addMonth()->year)
            ->where('is_paid', false);
    }

    public function scopeMonthly($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Monthly);
    }

    public function scopeMonthlyUnpaid($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Monthly)
            ->where('is_paid', false)
            ->whereDay('paid_on', '>=', now()->day);
    }

    public function scopeMonthlyAllUnpaid($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Monthly)
            ->where('is_paid', false);
    }

    public function scopeYearlyUnpaid($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->where('is_paid', false)
            ->whereDay('paid_on', '>=', now()->day)
            ->whereMonth('paid_on', '>=', now()->month);
    }

    public function scopeYearlyUnpaidDueThisMonth($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->where('is_paid', false)
            ->whereDay('paid_on', '>=', now()->day)
            ->whereMonth('paid_on', '=', now()->month);
    }

    public function scopeYearlyDueNextMonth($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->whereMonth('paid_on', '=', now()->addMonth()->month);
    }

    public function scopeYearlyUnpaidAll($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->where('is_paid', false);
    }

    public function scopeOneTimeUnpaid($query)
    {
        return $query
            ->whereMorphedTo('spend', Spend::class)
            ->where('is_paid', false);
    }

    public function scopeYearlyDueThisMonth($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->whereMonth('paid_on', '=', now()->month);
    }
}
