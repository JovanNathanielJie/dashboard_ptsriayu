<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\AssociationRule;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // BAGIAN A: Ambil AnalysisRun GLOBAL terbaru yang sudah selesai analisis
        // (bukan filter by user, karena semua role perlu lihat hasil yang sama)
        $latestRun = AnalysisRun::where('status', 'done')
            ->whereNotNull('total_frequent_itemsets')
            ->latest()
            ->first();

        // Hitung total analisis di seluruh sistem yang sudah selesai
        $totalAnalisis = AnalysisRun::where('status', 'done')
            ->whereNotNull('total_frequent_itemsets')
            ->count();

        // Total transaksi dari semua analisis yang selesai
        $totalTransaksi = AnalysisRun::where('status', 'done')
            ->whereNotNull('total_frequent_itemsets')
            ->withCount('transaksiItems')
            ->get()
            ->sum('transaksi_items_count');

        // Periode aktif dari run terbaru
        $periodeAktif = $latestRun?->periode_akhir?->year ?? now()->year;

        // Status sistem
        $statusSistem = $totalAnalisis > 0 ? 'Normal' : 'Belum ada data';

        // Activity metrics berdasarkan run terbaru
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

        // BAGIAN C: Ambil 10 association rules dengan lift tertinggi untuk chart
        $topRules = [];
        if ($latestRun) {
            $topRules = $latestRun->associationRules()
                ->orderBy('lift', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($rule) {
                    return [
                        'label' => $rule->antecedent . ' → ' . $rule->consequent,
                        'lift' => (float) $rule->lift,
                        'antecedent' => $rule->antecedent,
                        'consequent' => $rule->consequent,
                        'support' => (float) $rule->support,
                        'confidence' => (float) $rule->confidence,
                    ];
                })
                ->toArray();
        }

        return view('dashboard', compact(
            'totalAnalisis',
            'totalTransaksi',
            'periodeAktif',
            'statusSistem',
            'latestRun',
            'activityMetrics',
            'topRules'
        ));
    }
}
