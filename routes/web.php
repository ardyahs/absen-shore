<?php

use App\Http\Controllers\AbsenController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth', 'peran'])->group(function () {
    Route::get('/', [AuthController::class, 'home'])->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/akun', [AkunController::class, 'show'])->name('akun');
    Route::put('/akun/kata-sandi', [AkunController::class, 'kataSandi'])->name('akun.sandi');
    Route::get('/foto/{attendance}', [AbsenController::class, 'foto'])->name('foto');

    Route::middleware('peran:karyawan')->group(function () {
        Route::get('/absen', [AbsenController::class, 'beranda'])->name('absen');
        Route::post('/absen', [AbsenController::class, 'store'])->name('absen.kirim');
        Route::get('/riwayat', [AbsenController::class, 'riwayat'])->name('riwayat');
    });

    Route::middleware('peran:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/tinjau/{attendance}', [Admin\DashboardController::class, 'tinjau'])->name('tinjau');
        Route::get('/rekap', [Admin\RekapController::class, 'index'])->name('rekap');
        Route::get('/rekap/csv', [Admin\RekapController::class, 'csv'])->name('rekap.csv');
        Route::get('/karyawan', [Admin\KaryawanController::class, 'index'])->name('karyawan');
        Route::post('/karyawan', [Admin\KaryawanController::class, 'store'])->name('karyawan.simpan');
        Route::patch('/karyawan/{user}/status', [Admin\KaryawanController::class, 'status'])->name('karyawan.status');
        Route::put('/karyawan/{user}/sandi', [Admin\KaryawanController::class, 'sandi'])->name('karyawan.sandi');
        Route::get('/pengaturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan');
        Route::put('/pengaturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.simpan');
    });
});
