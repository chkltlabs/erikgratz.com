<?php

namespace App\Models;

use App\Enums\Period;
use App\Models\Traits\GetsDumped;
use App\Models\Traits\HasPayments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodicSpend extends Model
{
    use HasFactory, GetsDumped, HasPayments;

    protected $fillable = [
        'period', 'name', 'start_date', 'end_date',
        'is_income','type','subtype'
    ];

    protected $casts = [
        'period' => Period::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function totalSpend(): Attribute
    {
        return Attribute::make(
            get: function () {
                $amount = 0;
                list($add, $startOf, $endOf) = Period::getCarbonMethods($this->period);
                $payments = $this->payments()->orderBy('paid_on')->get();
                foreach ($payments as $first) {
                    $next = $payments->where('paid_on', '>', $first->paid_on)->first();
                    $date = Carbon::parse($first->paid_on);

                    if (!is_null($next)) {
                        $nextPaidOn = Carbon::parse($next->paid_on);
                    }

                    while ((!isset($nextPaidOn) || $date->lt($nextPaidOn))
                        && $date->lte($this->end_date)) {
                        $amount += $first->amount;
                        $date->$add();
                    }

                    unset($nextPaidOn);
                }
                return $amount;
            }
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

    public static function getDailySpendChartData(): array
    {
        return self::getChartData(Period::Daily());
    }

    public static function getWeeklySpendChartData(): array
    {
        return self::getChartData(Period::Weekly());
    }

    public static function getMonthlySpendChartData(): array
    {
        return self::getChartData(Period::Monthly());
    }

    public static function getYearlySpendChartData(): array
    {
        return self::getChartData(Period::Yearly());
    }

    public static function getChartData(Period $period): array
    {
        return array_values(self::collapseChartDataForPeriod($period, self::getDailyChartDataForAll()));
    }

    public static function combineDailyCharts($rtn, $data): array
    {
        $earliestDate = Carbon::parse(min(
            array_key_first($rtn) ?? '2999-01-01',
            array_key_first($data) ?? '2999-01-01'
        ));
        $lastliestDate = Carbon::parse(max(
            array_key_last($rtn) ?? '1900-01-01',
            array_key_last($data) ?? '1900-01-01'
        ));
        while($earliestDate->lte($lastliestDate)) {
            $k = $earliestDate->format('Y-m-d');
            if (isset($rtn[$k])
                && !isset($data[$k])) {
                //leave rtn alone
            } else if (!isset($rtn[$k])
                && isset($data[$k])){
                $rtn[$k] = $data[$k];
            } else if (isset($rtn[$k])
                && isset($data[$k])) {
                $rtn[$k] = [
                    'y' => round($rtn[$k]['y'] + $data[$k]['y'], 2),
                    'x' => Carbon::parse($k),
                ];
            }
            $earliestDate->addDay();
        }
        return $rtn;
    }
    public static function getDailyChartDataForAll(): array
    {
        $pSpends = self::query()->orderBy('start_date')->get();
        $rtn = [];
        foreach ($pSpends as $pSpend) {
            $rtn = self::combineDailyCharts($rtn, $pSpend->getDailyChartData());
        }

        return $rtn;
    }

    public function getDailyChartData()
    {
        $rtn = [];
        $payments = $this->payments()->orderBy('paid_on')->get();
        foreach ($payments as $first) {
            $next = $payments->where('paid_on', '>', $first->paid_on)->first();
            $date = Carbon::parse($first->paid_on);

            if (!is_null($next)) {
                $nextPaidOn = Carbon::parse($next->paid_on);
            }

            while ((!isset($nextPaidOn) || $date->lt($nextPaidOn))
                && $date->lte($this->end_date)) {
                $dailyDivisor = self::getDailyDivisor($this->period, $date);
                $rtn[$date->format('Y-m-d')] = [
                    'y' => round($first->amount / $dailyDivisor,2),
                    'x' => Carbon::parse($date->toDateString()),
                ];
                $date->addDay();
            }

            unset($nextPaidOn);
        }
        return $rtn;
    }

    public static function getDailyDivisor(Period $period, Carbon $date): int
    {
        return match ($period->value) {
            Period::Daily => 1,
            Period::Weekly => 7,
            Period::Monthly => $date->daysInMonth,
            Period::Yearly => $date->daysInYear,
        };
    }

    public static function collapseChartDataForPeriod(Period $period, array $data): array
    {
        if ($period->value == Period::Daily){
            return $data;
        }
        list($add, $startOf, $endOf) = Period::getCarbonMethods($period);
        $rtn = [];
        $firstDate = Carbon::parse(array_key_first($data))->$startOf();
        $lastDate = Carbon::parse(array_key_last($data))->$endOf();
        while ($firstDate->lte($lastDate)) {
            $rangeDate = $firstDate->copy()->$add();
            $amount = 0;
            $keyString = $firstDate->format('Y-m-d');
            $carbonCopy = $firstDate->copy();
            while ($firstDate->lte($rangeDate)) {
                if (array_key_exists($firstDate->format('Y-m-d'), $data)) {
                    $amount += $data[$firstDate->format('Y-m-d')]['y'];
                }
                $firstDate->addDay();
            }
            $rtn[$keyString] = [
                'y' => round($amount,2),
                'x' => Carbon::parse($carbonCopy->toDateString()),
            ];
        }
        return $rtn;
    }

}
