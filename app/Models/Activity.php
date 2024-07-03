<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'start_date', 'end_date',
    ];

    public function spends()
    {
        return $this->hasMany(Spend::class);
    }

    public function totalSpend(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->spends()->whereIsIncome(false)
                ->joinRelation('payments')->sum('amount')
                - $this->spends()->whereIsIncome(true)
                    ->joinRelation('payments')->sum('amount')
        );
    }

    public function totalDays(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->start_date)->diffInDays($this->end_date) + 1 //add 1, otherwise the first day doesn't count
        );
    }

    //normalized spend per day, for estimating proportional living expense
    public function normalizedTotalSpend(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->totalSpend / $this->totalDays)
        );
    }

    public function paid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->spends()->whereIsIncome(false)
                ->joinRelation('payments')
                ->where('payments.is_paid', true)
                ->sum('amount')
        );
    }

    public function unpaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->spends()->whereIsIncome(false)
                ->joinRelation('payments')
                ->where('payments.is_paid', false)
                ->sum('amount')
        );
    }
}
