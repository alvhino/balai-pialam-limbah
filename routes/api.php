<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrukController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\KunjunganController;

Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/download-qr/{filename}', [TrukController::class, 'downloadQR'])->name('download.qr');

Route::middleware('LoginMiddleware')->group(function () {

    Route::middleware('AdminMiddleware')->group(function () {
        Route::get('/user/search', [UserController::class, 'search']);
        Route::resource('/truk', TrukController::class);
        Route::resource('user', UserController::class);
        Route::resource('/kunjungan', KunjunganController::class);
    });

    Route::middleware('PetugasMiddleware')->group(function () {
        Route::get('/user/search', [UserController::class, 'search']);
        Route::resource('/kunjungan', KunjunganController::class);
        Route::resource('user', UserController::class);
    });

});
