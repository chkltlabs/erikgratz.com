<?php

namespace App\Models;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Traits\GetsDumped;
use App\Models\Traits\HasPayments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Spend extends Model
{
    use GetsDumped, HasFactory, HasPayments;

    protected $fillable = [
        'name',
        'is_income',
        'type',
        'subtype',
        'activity_id',
    ];

    protected $casts = [
        'type' => SpendType::class,
        'subtype' => SpendSubtype::class,
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function getDailyChartData(): array
    {
        $rtn = [];
        $payments = $this->payments()->orderBy('paid_on')->get();
        foreach ($payments as $first) {
            $date = Carbon::parse($first->paid_on ?? $this->activity->start_date);
            $k = $date->format('Y-m-d');
            if (isset($rtn[$k])) {
                $amt = $rtn[$k]['y'] + $first->amount;
            } else {
                $amt = $first->amount;
            }
            $rtn[$k] = [
                'y' => round($amt, 2),
                'x' => Carbon::parse($date->toDateString()),
            ];

            unset($nextPaidOn);
        }

        return $rtn;
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(\App\Models\SimpleFin\SimpleFinTransaction::class, 'spend');
    }

    public function rules(): MorphMany
    {
        return $this->morphMany(\App\Models\SimpleFinRule::class, 'spend');
    }

    public function confirmedTransactions(): MorphMany
    {
        return $this->transactions()->where('is_confirmed', true);
    }

    public function actualSpend(): Attribute
    {
        return Attribute::make(
            get: function () {
                $sum = $this->confirmedTransactions()->sum('amount');
                return $this->is_income ? $sum : abs($sum);
            }
        );
    }

    public function totalSpend(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->sum('amount')
        );
    }

    public function variance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->actual_spend - $this->total_spend
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->activity?->name ? ($this->activity->name . ' • ') : '')
                . $this->name
                . ($this->activity?->start_date ? (' • ' . $this->activity->start_date->format('M jS')) : '')
                . ($this->activity?->end_date ? (' - ' . $this->activity->end_date->format('M jS')) : ''),
        );
    }
}
