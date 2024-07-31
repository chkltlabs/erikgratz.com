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

    public function spendTypePercentages(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->percentages('type')
        );
    }

    public function spendSubtypePercentages(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->percentages('subtype')
        );
    }

    private function percentages($typeCol): array
    {
        $percentages = [];
        if ($this->total_spend == 0) {
            return $percentages;
        }
        foreach ($this->spends()->whereIsIncome(false)->get() as $spend) {
            $perc = (float) ($spend->amount / $this->total_spend) * 100;
            $type = $spend->$typeCol->value;
            if (!isset($percentages[$type])) {
                $percentages[$type] = 0;
            }
            $percentages[$type] += $perc;
        }
        return $percentages;
    }

    public function daysByMonth(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);

                if ($start->month == $end->month) {
                    return [
                        $start->format('M') => $start->diffInDays($end)
                    ];
                }
                $rtn = [];
                while ($start->lte($end)) {
                    $endThisMonth = $start->clone()->endOfMonth();
                    $rtn[$start->format('M')] = $start->diffInDays($endThisMonth);
                    $start->addMonth()->startOfMonth();
                }
                return $rtn;
            }
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
