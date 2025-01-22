<?php

namespace App\Providers;

use App\Models\PeriodicSpend;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
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
        //
        Schema::defaultStringLength(191);

        Relation::enforceMorphMap([
            'spend' => Spend::class,
            'periodic_spend' => PeriodicSpend::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
