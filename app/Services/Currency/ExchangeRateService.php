<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Enums\CurrencyCode;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function __construct(
        protected FrankfurterExchangeRateProvider $frankfurter,
        protected ExchangeRateApiProvider $exchangeRateApi,
    ) {}

    public function getToUsdMultiplier(CurrencyCode $currency, ?Carbon $date = null): float
    {
        if ($currency->isUsd()) {
            return 1.0;
        }

        $dateKey = ($date ?? now())->toDateString();
        $rates = $this->getCachedRates($dateKey);

        if (! array_key_exists($currency->value, $rates)) {
            $this->ensureRatesForDate($dateKey, [$currency->value]);
            $rates = $this->getCachedRates($dateKey);
        }

        return $rates[$currency->value] ?? 1.0;
    }

    public function convertToUsd(float $amount, CurrencyCode $currency, ?Carbon $date = null): float
    {
        return round($amount * $this->getToUsdMultiplier($currency, $date), 2);
    }

    /**
     * @param  Builder<Account>  $query
     */
    public function sumBalancesInUsd(Builder $query): float
    {
        return round(
            $query->get(['balance', 'currency'])
                ->sum(fn (Account $account): float => $this->convertToUsd(
                    (float) $account->balance,
                    $account->currency,
                )),
            2,
        );
    }

    public function refreshRatesForAccounts(): void
    {
        $currencies = Account::query()
            ->distinct()
            ->pluck('currency')
            ->map(fn ($value) => $value instanceof CurrencyCode ? $value->value : (string) $value)
            ->unique()
            ->values()
            ->all();

        $nonUsd = array_values(array_filter(
            $currencies,
            fn (string $code): bool => strtoupper($code) !== 'USD',
        ));

        $multipliers = ['USD' => 1.0];

        if ($nonUsd !== []) {
            $fetched = $this->fetchMultipliersWithFailover($nonUsd, now());
            $multipliers = array_merge($multipliers, $fetched);
        }

        $this->putCachedRates(now()->toDateString(), $multipliers);
        Cache::forget('stateDumps');
    }

    /**
     * @param  list<string>  $currencies
     * @return array<string, float>
     */
    public function multipliersForDump(array $currencies): array
    {
        $nonUsd = array_values(array_filter(
            $currencies,
            fn (string $code): bool => strtoupper($code) !== 'USD',
        ));

        $multipliers = ['USD' => 1.0];

        if ($nonUsd === []) {
            return $multipliers;
        }

        $dateKey = now()->toDateString();
        $cached = $this->getCachedRates($dateKey);
        $missing = array_values(array_filter(
            $nonUsd,
            fn (string $code): bool => ! array_key_exists($code, $cached),
        ));

        if ($missing !== []) {
            $this->refreshRatesForAccounts();
            $cached = $this->getCachedRates($dateKey);
        }

        foreach ($nonUsd as $code) {
            $multipliers[$code] = $cached[$code] ?? 1.0;
        }

        return $multipliers;
    }

    /**
     * @param  list<string>  $currencyCodes
     */
    public function ensureRatesForDate(string $dateKey, array $currencyCodes): void
    {
        $nonUsd = array_values(array_filter(
            $currencyCodes,
            fn (string $code): bool => strtoupper($code) !== 'USD',
        ));

        if ($nonUsd === []) {
            return;
        }

        $cached = $this->getCachedRates($dateKey);
        $missing = array_values(array_filter(
            $nonUsd,
            fn (string $code): bool => ! array_key_exists($code, $cached),
        ));

        if ($missing === []) {
            return;
        }

        $date = Carbon::parse($dateKey);
        $fetched = $this->fetchMultipliersWithFailover($missing, $date);
        $this->putCachedRates($dateKey, array_merge(['USD' => 1.0], $cached, $fetched));
    }

    /**
     * @param  list<string>  $currencies
     * @return array<string, float>
     */
    protected function fetchMultipliersWithFailover(array $currencies, Carbon $date): array
    {
        try {
            return $this->frankfurter->fetchToUsdMultipliers($currencies, $date);
        } catch (\Throwable $e) {
            Log::warning('Frankfurter exchange rate fetch failed, trying ExchangeRate-API.', [
                'message' => $e->getMessage(),
                'date' => $date->toDateString(),
            ]);
        }

        try {
            return $this->exchangeRateApi->fetchToUsdMultipliers($currencies, $date);
        } catch (\Throwable $e) {
            Log::error('Exchange rate fetch failed for all providers.', [
                'currencies' => $currencies,
                'date' => $date->toDateString(),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, float>
     */
    protected function getCachedRates(string $dateKey): array
    {
        return Cache::get($this->cacheKey($dateKey), []);
    }

    /**
     * @param  array<string, float>  $multipliers
     */
    protected function putCachedRates(string $dateKey, array $multipliers): void
    {
        $date = Carbon::parse($dateKey);
        $ttl = $date->isToday() ? now()->endOfDay() : now()->addYear();

        Cache::put($this->cacheKey($dateKey), $multipliers, $ttl);
    }

    protected function cacheKey(string $dateKey): string
    {
        return config('currency.cache_key').'.'.$dateKey;
    }
}
