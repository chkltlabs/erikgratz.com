<?php

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
});

require __DIR__.'/auth.php';
