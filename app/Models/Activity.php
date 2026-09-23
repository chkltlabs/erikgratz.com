<?php

namespace App\Models;

use App\Enums\TravelMethod;
use App\Models\Traits\GetsDumped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'location_name',
        'latitude',
        'longitude',
        'travel_method',
        'color',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'travel_method' => TravelMethod::class,
    ];

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function spends()
    {
        return $this->hasMany(Spend::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PointRedemption::class);
    }

    const ARCHIVE_DAY_GRACE = 15;

    public function archived(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->end_date)->lt(now()->subDays(self::ARCHIVE_DAY_GRACE))
        );
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

    /**
     * Visa-style stay length: first and last calendar days both count.
     */
    public static function inclusiveDayCount(\DateTimeInterface|string $start, \DateTimeInterface|string $end): int
    {
        return (int) Carbon::parse($start)->startOfDay()->diffInDays(
            Carbon::parse($end)->startOfDay(),
            absolute: true,
        ) + 1;
    }

    public static function formatInclusiveDayCount(int $days): string
    {
        return $days === 1 ? '1 day' : $days.' days';
    }

    public function totalDays(): Attribute
    {
        return Attribute::make(
            get: fn () => self::inclusiveDayCount($this->start_date, $this->end_date)
        );
    }

    // normalized spend per day, for estimating proportional living expense
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
            if (! isset($percentages[$type])) {
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
                        $start->format('M') => $start->diffInDays($end),
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

    public function getDailyChartData(): array
    {
        $rtn = [];
        foreach ($this->spends()->whereIsIncome(false)->get() as $spend) {
            $rtn = PeriodicSpend::combineDailyCharts($rtn, $spend->getDailyChartData());
        }

        return $rtn;
    }

    public static function getDailyChartDataForAll(): array
    {
        $rtn = [];
        $activities = Activity::orderBy('start_date')->get();
        foreach ($activities as $activity) {
            $rtn = PeriodicSpend::combineDailyCharts($rtn, $activity->getDailyChartData());
        }

        return $rtn;
    }
}
