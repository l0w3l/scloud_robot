<?php

use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/soundcloud/stream/{hash}', [StreamController::class, 'stream'])
    ->where('hash', '.*'); // Это позволит проглотить и хэш, и .mp3 в конце

Route::prefix('/soundcloud')->group(function () {
    Route::options('/stream/{hash}.mp3', [StreamController::class, 'stream']);
});
