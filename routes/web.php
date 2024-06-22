<?php

use App\Http\Controllers\ContactController;
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
$exitCode = \Illuminate\Support\Facades\Artisan::call('storage:link', []);
//echo $exitCode; // 0 exit code for no errors.

Route::get('/', 'InertiaPageController@getIndex')->name('home');

Route::get('/play', 'InertiaPageController@getPlay');

Route::get('/mock/{page}', 'InertiaPageController@getMock');

Route::get('/contact', 'InertiaPageController@getContact')->name('contact');

Route::get('/wedding', 'InertiaPageController@getWedding');

Route::resource('contacts', ContactController::class)
    ->except('update', 'destroy');

Route::get('/blog', 'InertiaPageController@getBlog');

Route::get('/portfolio', 'InertiaPageController@getPortfolio');

Route::get('/donate', 'InertiaPageController@getDonate');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('contacts', \App\Http\Controllers\ContactController::class)->only(
        //'update',
        'destroy'
    );

    // 2023-06-24 : Filament dashboard replaces breeze
    Route::get('/dashboard', 'InertiaDashboardController@getDashboard')->name('dashboard');
    // ->redirect(route('filament.admin.pages.dashboard'))

    Route::get('/blog/listing', 'InertiaDashboardController@getBlogListing')->name('posts');

    Route::get('/blog/edit/{blog_post_id}', 'InertiaDashboardController@getBlogEdit');

    Route::post('/blog/edit/{blog_post_id}', 'InertiaDashboardController@postBlogEdit');

    Route::get('/blog/new', 'InertiaDashboardController@getBlogNew');

    Route::post('/blog/new', 'InertiaDashboardController@postBlogNew');
});

require __DIR__.'/auth.php';
