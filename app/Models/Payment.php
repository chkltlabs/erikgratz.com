<?php

namespace App\Models;

use App\Enums\Period;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = ['spend_id', 'spend_type', 'amount', 'is_paid', 'paid_on', 'card_id'];

    public function casts()
    {
        return [
            'paid_on' => 'date',
            'is_paid' => 'boolean',
        ];
    }

    public function spend(): MorphTo
    {
        return $this->morphTo();
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
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
            ->whereYear('paid_on', now()->year);
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
            ->whereYear('paid_on', now()->year)
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
            ->whereDay('paid_on', '>=', now()->day);
    }

    public function scopeYearlyUnpaid($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->whereDay('paid_on', '>=', now()->day)
            ->whereMonth('paid_on', '>=', now()->month);
    }

    public function scopeYearlyUnpaidDueThisMonth($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->whereDay('paid_on', '>=', now()->day)
            ->whereMonth('paid_on', '=', now()->month);
    }

    public function scopeYearlyUnpaidDueNextMonth($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->whereMonth('paid_on', '=', now()->addMonth()->month);
    }

    public function scopeYearlyDueThisMonth($query)
    {
        return $query
            ->whereMorphRelation('spend', PeriodicSpend::class, 'period', '=', Period::Yearly)
            ->whereMonth('paid_on', '=', now()->month);
    }
}
