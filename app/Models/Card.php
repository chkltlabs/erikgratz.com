<?php

namespace App\Models;

use App\Enums\PointsProgram;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\GetsDumped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use BelongsToUser, GetsDumped, HasFactory;

    protected $fillable = [
        'name', 'user_id', 'limit',
        'due_date', 'statement_date',
        'annual_fee', 'balance', 'pending',
        'interest_saving_balance',
        'interest_free_balance',
        'interest_free_balance_payment',
        'points_balance', 'points_bonus',
        'points_bonus_spend', 'date_opened',
        'points_bonus_period', 'color',
        'points_program',
    ];

    protected $casts = [
        'date_opened' => 'date:Y-m-d',
        'points_program' => PointsProgram::class,
    ];

    public function benefits(): HasMany
    {
        return $this->hasMany(CardBenefit::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paid_payments()
    {
        return $this->payments()
            ->where('is_paid', true);
    }

    public function planned_payments()
    {
        return $this->payments()
            ->where('is_paid', false);
    }

    public function amountDue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->interest_saving_balance != 0
                ? $this->interest_saving_balance
                : ($this->balance
                    + $this->pending
                    + $this->interest_free_balance_payment
                    - $this->interest_free_balance)
        );
    }

    public function hasSatisfiedSub(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => now()->gt($this->points_bonus_deadline) ||
                $this->balance
                + $this->pending
                + $this->planned_payments
                    ->where('paid_on', '<=', $this->points_bonus_deadline)
                    ->sum('amount')
                > $this->points_bonus_spend,
        );
    }

    public function pointsBonusDeadline(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->date_opened)->add($this->points_bonus_period ?? '3 months')
        );
    }

    public function plannedPaymentTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->planned_payments()->sum('amount')
        );
    }

    public function paidPaymentTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->paid_payments()->sum('amount')
        );
    }

    public function scopeFutureDue($query)
    {
        return $query->where('due_date', '>=', now()->day);
    }

    public function scopePastDue($query)
    {
        return $query->where('due_date', '<', now()->day);
    }

    public function scopeNoISBYet($query)
    {
        return $query->where('balance', '>', 0)
            ->where('interest_saving_balance', 0);
    }

    public function scopeHasIsb($query)
    {
        return $query->where('interest_saving_balance', '!=', 0);
    }

    /**
     * SumCard-style stock: balance + pending - IFB + IFBP.
     */
    public function stockBalance(): float
    {
        return (float) $this->balance
            + (float) $this->pending
            - (float) $this->interest_free_balance
            + (float) $this->interest_free_balance_payment;
    }

    /**
     * When a spend on this card becomes cash-flow due, from statement close → due date.
     */
    public function cashflowDueDateForSpendDate(Carbon $spendDate): Carbon
    {
        $statementDay = max(1, (int) $this->statement_date);
        $dueDay = max(1, (int) $this->due_date);

        if ($spendDate->day <= $statementDay) {
            $statementClose = self::dateOnDay($spendDate, $statementDay);
        } else {
            $statementClose = self::dateOnDay($spendDate->copy()->addMonthNoOverflow(), $statementDay);
        }

        if ($dueDay > $statementDay) {
            return self::dateOnDay($statementClose, $dueDay);
        }

        return self::dateOnDay($statementClose->copy()->addMonthNoOverflow(), $dueDay);
    }

    public static function dateOnDay(Carbon $base, int $day): Carbon
    {
        $date = $base->copy()->startOfMonth()->startOfDay();

        return $date->day(min($day, $date->daysInMonth));
    }

    public function simpleFinAccount(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\App\Models\SimpleFin\SimpleFinAccount::class, 'associated');
    }
}
