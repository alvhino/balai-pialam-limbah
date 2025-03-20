<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrukController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\CekPhController;
use App\Http\Controllers\VolumeController;
use App\Http\Controllers\TransaksiController;

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::middleware('LoginMiddleware')->group(function () {

    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/download-qr/{filename}', [TrukController::class, 'downloadQR'])->name('download.qr');

    // Pendapatan
    Route::get('/pendapatan', [UserController::class, 'get_data']);

    // Cek PH
    Route::get('/cek-ph/search', [CekPhController::class, 'search']);
    Route::resource('cek-ph', CekPhController::class);

    // Volume
    Route::get('/volume/search', [VolumeController::class, 'searchVolume']);
    Route::resource('volume', VolumeController::class);

    // Transaksi
    Route::get('/transaksi/search', [TransaksiController::class, 'searchTransaksi']);
    Route::resource('transaksi', TransaksiController::class);

    // Kunjungan
    Route::post('/keluar', [KunjunganController::class, 'keluar']);
    Route::get('/kunjungan/search', [KunjunganController::class, 'searchKunjungan']);
    Route::resource('kunjungan', KunjunganController::class);
    
    // Admin
    Route::middleware('AdminMiddleware')->prefix('admin')->group(function () {
        Route::get('/user/search', [UserController::class, 'search']);
        Route::resource('user', UserController::class);
        Route::resource('truk', TrukController::class);
    });

    // Petugas
    Route::middleware('PetugasMiddleware')->prefix('petugas')->group(function () {
        Route::get('/user', [UserController::class, 'index']);
        Route::post('/user', [UserController::class, 'store']);

        Route::get('/truk', [TrukController::class, 'index']);
        Route::post('/truk', [TrukController::class, 'store']);
        Route::get('/truk/{uid_truk}', [TrukController::class, 'show']);

        Route::get('/kunjungan', [KunjunganController::class, 'index']);
        Route::post('/kunjungan', [KunjunganController::class, 'store']);
    });

    // Executive
    Route::middleware('ExecutiveMiddleware')->prefix('executive')->group(function () {
       
    });

});
