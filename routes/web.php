<?php

use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/soundcloud')->group(function () {
    Route::get('/stream', [StreamController::class, 'stream']);
    Route::options('/stream', [StreamController::class, 'stream']);
});
