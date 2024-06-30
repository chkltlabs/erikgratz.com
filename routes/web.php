<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\InertiaDashboardController;
use App\Http\Controllers\InertiaPageController;
use App\Livewire\Counter;
use App\Livewire\Page\Home;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//$exitCode = \Illuminate\Support\Facades\Artisan::call('storage:link', []);
//echo $exitCode; // 0 exit code for no errors.

Route::get('/', [InertiaPageController::class, 'getIndex'])->name('home');

Route::get('/play', [InertiaPageController::class, 'getPlay']);

Route::get('/mock/{page}', [InertiaPageController::class, 'getMock']);

Route::get('/contact', [InertiaPageController::class, 'getContact'])->name('contact');

Route::get('/wedding', [InertiaPageController::class, 'getWedding']);

Route::resource('contacts', ContactController::class)
    ->except('update', 'destroy');

Route::get('/blog', [InertiaPageController::class, 'getBlog']);

Route::get('/portfolio', [InertiaPageController::class, 'getPortfolio']);

Route::get('/donate', [InertiaPageController::class, 'getDonate']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('contacts', \App\Http\Controllers\ContactController::class)->only(
        //'update',
        'destroy'
    );

    // 2023-06-24 : Filament dashboard replaces breeze
    Route::get('/dashboard', [InertiaDashboardController::class, 'getDashboard'])->name('dashboard');
    // ->redirect(route('filament.admin.pages.dashboard']))

    Route::get('/blog/listing', [InertiaDashboardController::class, 'getBlogListing'])->name('posts');

    Route::get('/blog/edit/{blog_post_id}', [InertiaDashboardController::class, 'getBlogEdit']);

    Route::post('/blog/edit/{blog_post_id}', [InertiaDashboardController::class, 'postBlogEdit']);

    Route::get('/blog/new', [InertiaDashboardController::class, 'getBlogNew']);

    Route::post('/blog/new', [InertiaDashboardController::class, 'postBlogNew']);

    //-------------------
    // Livewire Redo
    //-------------------

    Route::get('/counter', Counter::class); //example

    Route::prefix('redo')->group(function () {
        Route::get('/', Home::class);
    });
});

require __DIR__.'/auth.php';
