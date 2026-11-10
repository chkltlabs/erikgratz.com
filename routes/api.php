<?php

use App\Http\Controllers\Api\TerminalContactController;
use App\Http\Controllers\Api\TerminalFitController;
use App\Http\Controllers\Api\TerminalSnapshotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('terminal')->prefix('terminal')->group(function () {
    Route::get('snapshot', TerminalSnapshotController::class)
        ->name('terminal.snapshot');
    Route::post('contact', TerminalContactController::class)
        ->name('terminal.contact');
    Route::post('fit', TerminalFitController::class)
        ->name('terminal.fit');
});
