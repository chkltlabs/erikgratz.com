<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'limit', 'due_date',
        'balance', 'pending',
        'interest_saving_balance',
        'interest_free_balance',
        'interest_free_balance_payment',
        'points_balance', 'points_bonus',
        'points_bonus_spend','date_opened',
        'points_bonus_period','color'
    ];

    protected $casts = [
        'date_opened' => 'date',
    ];

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
}
