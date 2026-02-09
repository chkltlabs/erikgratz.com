<?php

namespace App\Models;

use App\Models\Traits\HasPayments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanAgainstSavings extends Model
{
    use HasFactory, HasPayments;

    public $timestamps = false;

    protected $fillable = [
        'balance','reason','loan_date','is_paid','paid_on','card_id'
    ];

    protected $casts = [
        'loan_date' => 'date:Y-m-d',
        'paid_on' => 'date:Y-m-d',
    ];

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('paid_on', now()->month)
            ->whereYear('paid_on', now()->year);
    }

    public function scopeNextMonth($query)
    {
        return $query->whereMonth('paid_on', now()->addMonth()->month)
            ->whereYear('paid_on', now()->addMonth()->year);
    }

    public function scopeThirdMonth($query)
    {
        return $query->whereMonth('paid_on', now()->addMonths(2)->month)
            ->whereYear('paid_on', now()->addMonths(2)->year);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }
}
