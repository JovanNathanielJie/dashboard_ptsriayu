<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UploadController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = Auth::user();

    if (! $user instanceof User) {
        return redirect()->route('login');
    }

    $analysisRuns = $user->analysisRuns()->latest('created_at');
    $latestRun = $analysisRuns->first();

    $totalAnalisis = $analysisRuns->count();
    $totalTransaksi = $user->analysisRuns()
        ->withCount('transaksiItems')
        ->get()
        ->sum('transaksi_items_count');

    $periodeAktif = $latestRun?->periode_akhir?->year ?? now()->year;
    $statusSistem = $totalAnalisis > 0 ? 'Normal' : 'Belum ada data';

    $activityMetrics = [
        [
            'label' => 'Data transaksi masuk',
            'value' => $latestRun && $latestRun->total_baris_raw
                ? min(100, max(0, (int) round((($latestRun->total_baris_clean ?? 0) / $latestRun->total_baris_raw) * 100)))
                : 0,
            'detail' => $latestRun && $latestRun->total_baris_raw
                ? (($latestRun->total_baris_clean ?? 0) . ' dari ' . $latestRun->total_baris_raw . ' baris valid')
                : 'Belum ada data unggah',
            'color' => 'bg-[#A1582F]',
        ],
        [
            'label' => 'Pola pembelian terdeteksi',
            'value' => $latestRun && $latestRun->total_frequent_itemsets
                ? min(100, max(0, (int) round((($latestRun->total_association_rules ?? 0) / max(1, $latestRun->total_frequent_itemsets)) * 100)))
                : 0,
            'detail' => $latestRun && $latestRun->total_frequent_itemsets
                ? (($latestRun->total_association_rules ?? 0) . ' aturan dari ' . $latestRun->total_frequent_itemsets . ' itemset')
                : 'Belum ada pola terbentuk',
            'color' => 'bg-[#F4C76F]',
        ],
        [
            'label' => 'Ketersediaan data gudang',
            'value' => $latestRun && ($latestRun->total_faktur_unik ?? 0)
                ? min(100, max(0, (int) round((($latestRun->total_produk_unik ?? 0) / max(1, $latestRun->total_faktur_unik)) * 100)))
                : 0,
            'detail' => $latestRun && ($latestRun->total_produk_unik ?? 0)
                ? (($latestRun->total_produk_unik ?? 0) . ' produk terdaftar dalam ' . ($latestRun->total_faktur_unik ?? 0) . ' faktur')
                : 'Belum ada data gudang',
            'color' => 'bg-[#2F8F74]',
        ],
    ];

    return view('dashboard', compact(
        'totalAnalisis',
        'totalTransaksi',
        'periodeAktif',
        'statusSistem',
        'latestRun',
        'activityMetrics'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin_penjualan'])->group(function () {
    Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
