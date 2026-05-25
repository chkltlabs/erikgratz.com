<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Illuminate\Support\Facades\Http;

class FrankfurterExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * @param  list<string>  $currencies
     * @return array<string, float>
     */
    public function fetchToUsdMultipliers(array $currencies): array
    {
        if ($currencies === []) {
            return [];
        }

        $symbols = implode(',', $currencies);
        $baseUrl = rtrim(config('currency.frankfurter.base_url'), '/');

        $response = Http::timeout(15)->get("{$baseUrl}/latest", [
            'from' => 'USD',
            'to' => $symbols,
        ]);

        $response->throw();

        /** @var array{rates?: array<string, float|int>} $payload */
        $payload = $response->json();

        if (! isset($payload['rates']) || ! is_array($payload['rates'])) {
            throw new \RuntimeException('Frankfurter response missing rates.');
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
                throw new \RuntimeException("Frankfurter missing rate for {$currency}.");
            }

            $perUsd = (float) $ratesPerUsd[$currency];

            if ($perUsd <= 0) {
                throw new \RuntimeException("Frankfurter invalid rate for {$currency}.");
            }

            $multipliers[$currency] = 1 / $perUsd;
        }

        return $multipliers;
    }
}
