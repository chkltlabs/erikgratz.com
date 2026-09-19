<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BenefitTrackingMode;
use App\Models\Activity;
use App\Models\CardBenefit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UnusedBenefitFeed
{
    /**
     * @return Collection<int, CardBenefit>
     */
    public function due(bool $showAllLocations = false): Collection
    {
        return $this->dueQuery($showAllLocations)->get();
    }

    public function dueQuery(bool $showAllLocations = false): Builder
    {
        $query = $this->unusedQuery()
            ->where('tracking_mode', '!=', BenefitTrackingMode::Auto);

        if (! $showAllLocations) {
            $this->constrainToUpcomingLocations($query);
        }

        return $query;
    }

    /**
     * @return Collection<int, CardBenefit>
     */
    public function auto(): Collection
    {
        return $this->autoQuery()->get();
    }

    public function autoQuery(): Builder
    {
        return $this->unusedQuery()
            ->whereIn('tracking_mode', [BenefitTrackingMode::Auto, BenefitTrackingMode::Ignore]);
    }

    /**
     * @return list<string>
     */
    public function upcomingLocations(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->startOfDay();

        return Activity::query()
            ->whereDate('end_date', '>=', $asOf)
            ->pluck('location_name')
            ->filter()
            ->values()
            ->all();
    }

    protected function unusedQuery(): Builder
    {
        return CardBenefit::query()
            ->with(['card', 'loyaltyMembership.program', 'usages'])
            ->where('is_useable', true)
            ->where('is_used', false)
            ->orderByRaw('CASE WHEN tracking_mode = ? THEN 1 ELSE 0 END', [BenefitTrackingMode::Ignore])
            ->orderByRaw("COALESCE(next_refresh_at, '9999-12-31')");
    }

    protected function constrainToUpcomingLocations(Builder $query): void
    {
        $locations = $this->upcomingLocations();

        $query->where(function (Builder $query) use ($locations): void {
            $query->where(function (Builder $query): void {
                $query->where(fn (Builder $inner): Builder => $inner->whereNull('location_city')->orWhere('location_city', ''))
                    ->where(fn (Builder $inner): Builder => $inner->whereNull('location_country')->orWhere('location_country', ''));
            });

            foreach ($locations as $location) {
                $haystack = strtolower($location);

                $query->orWhere(function (Builder $query) use ($haystack): void {
                    $query->whereNotNull('location_city')
                        ->where('location_city', '!=', '')
                        ->whereRaw('? LIKE CONCAT("%", LOWER(location_city), "%")', [$haystack]);
                })->orWhere(function (Builder $query) use ($haystack): void {
                    $query->whereNotNull('location_country')
                        ->where('location_country', '!=', '')
                        ->whereRaw('? LIKE CONCAT("%", LOWER(location_country), "%")', [$haystack]);
                });
            }
        });
    }
}
