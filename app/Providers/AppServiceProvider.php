<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Foundation\Mix;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        FilamentAsset::register([
            Js::make('chart-js-plugins', __DIR__ . '/../../public/js/filament-chart-plugins.js')->module(),
        ]);
    }
}
