<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrukController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\KunjunganController;

Route::post('/login', [LoginController::class, 'login']);
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('LoginMiddleware')->group(function () {

    Route::middleware('AdminMiddleware')->group(function () {
        Route::get('/user/search', [UserController::class, 'search']);
        Route::resource('user', UserController::class);
        Route::resource('truk', TrukController::class);
        Route::get('/download-qr/{filename}', [TrukController::class, 'downloadQR'])->name('download.qr');
        Route::resource('/kunjungan', KunjunganController::class);
    });

    Route::middleware('PetugasMiddleware')->group(function () {
        Route::get('/user/search', [UserController::class, 'search']);
        Route::resource('user', UserController::class)->only([
            'index', 'show', 'store'
        ]);
        Route::resource('truk', TrukController::class)->only([
            'index', 'show', 'store'
        ]);
        Route::get('/download-qr/{filename}', [TrukController::class, 'downloadQR'])->name('download.qr');
        Route::resource('/kunjungan', KunjunganController::class);
    });
});
