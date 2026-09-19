<?php

namespace App\Providers;

use App\Ai\Retrieval\EmbeddingSpec;
use App\Database\PgsqlMigrationHooks;
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
use Pgvector\Laravel\Schema as PgvectorSchema;

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
        $this->app->singleton(EmbeddingSpec::class, fn () => EmbeddingSpec::fromConfig());

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
    public function boot()
    {
        PgvectorSchema::register();
        $this->loadMigrationsFrom(database_path('migrations/pgsql'));
        PgsqlMigrationHooks::register();
    }
}
