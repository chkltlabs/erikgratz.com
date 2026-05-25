<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Currency\ExchangeRateService;
use Illuminate\Console\Command;

class RefreshExchangeRatesCommand extends Command
{
    protected $signature = 'fx:refresh';

    protected $description = 'Refresh cached USD exchange rates for account currencies';

    public function handle(ExchangeRateService $exchangeRateService): int
    {
        $exchangeRateService->refreshRatesForAccounts();

        $this->info('Exchange rates refreshed.');

        return self::SUCCESS;
    }
}
