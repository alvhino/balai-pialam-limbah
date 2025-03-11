<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrukController;
use App\Http\Controllers\LoginController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// route untuk user
Route::get('/user/search', [UserController::class, 'search']);
Route::resource('user', UserController::class);

// route untuk truk
Route::resource('truk', TrukController::class);

// Rute login dan logout
Route::get('login', [LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('login', [LoginController::class, 'login'])->name('login.proses')->middleware('guest');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');