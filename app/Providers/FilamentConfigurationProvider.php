<?php

namespace App\Providers;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
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
        // --------------------------------
        // Filament Validation Error reporting
        // --------------------------------
        Page::$reportValidationErrorUsing = function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };

        FilamentColor::register([
            'purp' => Color::hex('#371BB1'), // heart-purple
            'primary' => Color::hex('#371BB1'),
        ]);
    }
}
