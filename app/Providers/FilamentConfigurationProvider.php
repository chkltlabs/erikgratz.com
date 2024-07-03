<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Theme;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class FilamentConfigurationProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //--------------------------------
        //Filament Validation Error reporting
        //--------------------------------
        Page::$reportValidationErrorUsing = function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };

        Filament::serving(function () {
            // Using Vite
            Filament::registerViteTheme('resources/css/filament/admin/theme.css', 'build');
        });

        FilamentAsset::register([
            Js::make('chart-js-plugins', Vite::asset('resources/js/Filament/filament-chart-plugins.js'))->module(),
//        ]);

//            Theme::make('theme', Vite::asset('resources/css/filament/admin/theme.css')),

//            Css::make('build-css', Vite::asset('resources/sass/app.scss','build'))
        ]);

        //try fully switching to this method of theme customization
        FilamentColor::register([
            'purp' => Color::hex('#371BB1'), //heart-purple
            'primary' => Color::hex('#371BB1'),
        ]);
    }
}
