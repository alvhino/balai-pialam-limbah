<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrukController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\CekPhController;

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::middleware('LoginMiddleware')->group(function () {

    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/download-qr/{filename}', [TrukController::class, 'downloadQR'])->name('download.qr');
    Route::resource('cek-ph', CekPhController::class);
    
    Route::middleware('AdminMiddleware')->prefix('admin')->group(function () {
        Route::get('/user/search', [UserController::class, 'search']);
        Route::resource('user', UserController::class);
        Route::resource('truk', TrukController::class);
        Route::resource('kunjungan', KunjunganController::class);
    });

    Route::middleware('PetugasMiddleware')->prefix('petugas')->group(function () {
        Route::get('/user', [UserController::class, 'index']);
        Route::post('/user', [UserController::class, 'store']);

        Route::get('/truk', [TrukController::class, 'index']);
        Route::post('/truk', [TrukController::class, 'store']);
        Route::get('/truk/{uid_truk}', [TrukController::class, 'show']);

        Route::get('/kunjungan', [KunjunganController::class, 'index']);
        Route::post('/kunjungan', [KunjunganController::class, 'store']);
    });

});
