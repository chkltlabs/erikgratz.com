<?php

declare(strict_types=1);

namespace App\Services\Currency;

interface ExchangeRateProvider
{
    /**
     * @param  list<string>  $currencies  ISO 4217 codes excluding USD
     * @return array<string, float> currency code => multiply native balance by this for USD
     */
    public function fetchToUsdMultipliers(array $currencies): array;
}
