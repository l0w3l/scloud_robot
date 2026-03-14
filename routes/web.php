<?php

use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/soundcloud')->group(function () {
    Route::get('/stream/{hash}.mp3', [StreamController::class, 'stream'])->name('soundcloud.stream');
    Route::options('/stream/{hash}.mp3', [StreamController::class, 'stream']);
});
