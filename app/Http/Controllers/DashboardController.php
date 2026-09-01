<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\AssociationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil semua daftar run yang sudah selesai
        $allRuns = AnalysisRun::where('status', 'done')
            ->whereNotNull('total_frequent_itemsets')
            ->latest()
            ->get();

        // 2. Tangkap pilihan dropdown ('all' atau ID spesifik)
        $selectedRunId = $request->get('run_id', 'all');

        // $selectedRun: run yang dipilih dari dropdown (bisa null jika 'all')
        $selectedRun = ($selectedRunId !== 'all')
            ? $allRuns->firstWhere('id', $selectedRunId)
            : null;

        // $actualLatestRun: SELALU run paling baru, tidak terpengaruh dropdown
        $actualLatestRun = $allRuns->first();

        // 3. Statistik Global
        $totalAnalisis = $allRuns->count();

        $totalTransaksi = AnalysisRun::where('status', 'done')
            ->whereNotNull('total_frequent_itemsets')
            ->withCount('transaksiItems')
            ->get()
            ->sum('transaksi_items_count');

        $periodeAktif = $selectedRun
            ? ($selectedRun->periode_akhir?->year ?? now()->year)
            : 'Semua Periode';

        $statusSistem = $totalAnalisis > 0 ? 'Normal' : 'Belum ada data';

        // Reference Run untuk Activity Metrics (SELALU gunakan run terbaru)
        $metricRun = $actualLatestRun;

        $activityMetrics = [
            [
                'label' => 'Data transaksi masuk',
                'value' => $metricRun && $metricRun->total_baris_raw
                    ? min(100, max(0, (int) round((($metricRun->total_baris_clean ?? 0) / $metricRun->total_baris_raw) * 100)))
                    : 0,
                'detail' => $metricRun && $metricRun->total_baris_raw
                    ? (($metricRun->total_baris_clean ?? 0) . ' dari ' . $metricRun->total_baris_raw . ' baris valid')
                    : 'Belum ada data unggah',
                'color' => 'bg-[#A1582F]',
                'hex' => '#A1582F',
            ],
            [
                'label' => 'Pola pembelian terdeteksi',
                'value' => $metricRun && $metricRun->total_frequent_itemsets
                    ? min(100, max(0, (int) round((($metricRun->total_association_rules ?? 0) / max(1, $metricRun->total_frequent_itemsets)) * 100)))
                    : 0,
                'detail' => $metricRun && $metricRun->total_frequent_itemsets
                    ? (($metricRun->total_association_rules ?? 0) . ' aturan dari ' . $metricRun->total_frequent_itemsets . ' itemset')
                    : 'Belum ada pola terbentuk',
                'color' => 'bg-[#D99A29]',
                'hex' => '#D99A29',
            ],
            [
                'label' => 'Ketersediaan data gudang',
                'value' => $metricRun && ($metricRun->total_faktur_unik ?? 0)
                    ? min(100, max(0, (int) round((($metricRun->total_produk_unik ?? 0) / max(1, $metricRun->total_faktur_unik)) * 100)))
                    : 0,
                'detail' => $metricRun && ($metricRun->total_produk_unik ?? 0)
                    ? (($metricRun->total_produk_unik ?? 0) . ' produk terdaftar dalam ' . ($metricRun->total_faktur_unik ?? 0) . ' faktur')
                    : 'Belum ada data gudang',
                'color' => 'bg-[#1F6E56]',
                'hex' => '#1F6E56'
            ],
        ];

        // 4. Query Association Rules Top 10
        if ($selectedRunId === 'all') {
            // MODE GABUNGAN (ALL): Ambil rule dengan Lift tertinggi PER BARIS (bukan MAX per kolom terpisah)
            // Grouping berdasarkan antecedent-consequent, ambil baris dengan lift tertinggi per grup
            $topRules = AssociationRule::whereIn('analysis_run_id', $allRuns->pluck('id'))
                ->get()
                ->groupBy(function ($rule) {
                    return $rule->antecedent . '|' . $rule->consequent;
                })
                ->map(function ($group) {
                    return $group->sortByDesc('lift')->first();
                })
                ->sortByDesc('lift')
                ->take(10)
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
                ->values()
                ->toArray();
        } else {
            // MODE PERIODE SPESIFIK
            $topRules = [];
            if ($selectedRun) {
                $topRules = $selectedRun->associationRules()
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
        }

        return view('dashboard', compact(
            'allRuns',
            'selectedRun',
            'actualLatestRun',
            'selectedRunId',
            'totalAnalisis',
            'totalTransaksi',
            'periodeAktif',
            'statusSistem',
            'activityMetrics',
            'topRules'
        ));
    }
}
