<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Card;
use App\Models\LoanAgainstSavings;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use App\Services\Currency\ExchangeRateProvider;
use App\Services\Currency\FrankfurterExchangeRateProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ExchangeRateProvider::class, FrankfurterExchangeRateProvider::class);

        Schema::defaultStringLength(191);

        Relation::enforceMorphMap([
            'spend' => Spend::class,
            'periodic_spend' => PeriodicSpend::class,
            'loan_against_savings' => LoanAgainstSavings::class,
            'account' => Account::class,
            'card' => Card::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
