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
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function amount_due(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->interest_saving_balance !== 0
                ? $this->interest_saving_balance
                : ($this->balance
                    + $this->pending
                    + $this->interest_free_balance_payment
                    - $this->interest_free_balance)
        );
    }
}
