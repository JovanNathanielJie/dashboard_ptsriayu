<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Route Riwayat - Akses untuk direktur_utama dan admin_penjualan
Route::get('/riwayat', [RiwayatController::class, 'index'])
    ->middleware(['auth', 'role:direktur_utama,admin_penjualan'])
    ->name('riwayat.index');

Route::middleware(['auth', 'role:admin_penjualan'])->group(function () {
    Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
    Route::get('/analysis/{run}/parameter', [AnalysisController::class, 'parameter'])->name('analysis.parameter');
    Route::post('/analysis/{run}/process', [AnalysisController::class, 'process'])->name('analysis.process');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['role:direktur_utama'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
});

Route::middleware(['role:direktur_utama'])->group(function () {
    Route::get('/validasi', [ValidasiController::class, 'index'])->name('validasi.index');
    Route::get('/validasi/{run}', [ValidasiController::class, 'show'])->name('validasi.show');
    Route::post('/validasi/{run}/approve', [ValidasiController::class, 'approve'])->name('validasi.approve');
    Route::post('/validasi/{run}/reject', [ValidasiController::class, 'reject'])->name('validasi.reject');
});

require __DIR__.'/auth.php';
