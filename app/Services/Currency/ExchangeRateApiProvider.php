<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ExchangeRateApiProvider implements ExchangeRateProvider
{
    /**
     * @param  list<string>  $currencies
     * @return array<string, float>
     */
    public function fetchToUsdMultipliers(array $currencies, ?Carbon $date = null): array
    {
        if ($currencies === []) {
            return [];
        }

        $response = Http::timeout(15)->get(config('currency.exchange_rate_api.base_url'));

        $response->throw();

        /** @var array{result?: string, rates?: array<string, float|int>} $payload */
        $payload = $response->json();

        if (($payload['result'] ?? null) !== 'success' || ! isset($payload['rates']) || ! is_array($payload['rates'])) {
            throw new \RuntimeException('ExchangeRate-API response missing rates.');
        }

        return $this->ratesToMultipliers($payload['rates'], $currencies);
    }

    /**
     * @param  array<string, float|int>  $ratesPerUsd
     * @param  list<string>  $currencies
     * @return array<string, float>
     */
    protected function ratesToMultipliers(array $ratesPerUsd, array $currencies): array
    {
        $multipliers = [];

        foreach ($currencies as $currency) {
            if (! isset($ratesPerUsd[$currency])) {
                throw new \RuntimeException("ExchangeRate-API missing rate for {$currency}.");
            }

            $perUsd = (float) $ratesPerUsd[$currency];

            if ($perUsd <= 0) {
                throw new \RuntimeException("ExchangeRate-API invalid rate for {$currency}.");
            }

            $multipliers[$currency] = 1 / $perUsd;
        }

        return $multipliers;
    }
}
