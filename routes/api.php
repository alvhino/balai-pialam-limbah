<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrukController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// route untuk user
Route::get('/user/search', [UserController::class, 'search']);
Route::resource('user', UserController::class);

// route untuk truk
Route::resource('truk', TrukController::class);
Route::get('/download-qr/{filename}', [TrukController::class, 'downloadQR'])->name('download.qr');