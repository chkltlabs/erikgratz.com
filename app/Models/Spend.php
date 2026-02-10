<?php

namespace App\Models;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Traits\GetsDumped;
use App\Models\Traits\HasPayments;
use Carbon\Carbon;
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
}
