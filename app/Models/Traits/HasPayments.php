<?php

namespace App\Models\Traits;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasPayments
{
    protected static function bootHasPayments()
    {
        static::deleting(function ($model) {
            $model->payments()->delete();
        });
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'spend');
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->sum('amount')
        );
    }
}
