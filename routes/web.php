<?php

use App\Http\Controllers\LocationSearchController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect('/home'));

Volt::route('/home', 'page.home')->name('home');
Volt::route('/work', 'page.work');
Volt::route('/experience', 'page.experience');
Volt::route('/photo', 'page.photo');
Volt::route('/contact', 'page.contact')->name('contact');
Volt::route('/portfolio', 'page.portfolio');
Volt::route('/play', 'page.play');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('filament.admin.pages.dashboard'))->name('dashboard');
    Route::get('/admin/location-search', LocationSearchController::class)
        ->middleware('throttle:30,1')
        ->name('admin.location-search');
});

require __DIR__.'/auth.php';
