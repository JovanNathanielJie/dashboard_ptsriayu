<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\AssociationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Buat interpretasi dan rekomendasi untuk satu association rule.
     *
     * @param array $rule Array berisi: antecedent, consequent, support, confidence, lift
     * @return array Array berisi 'interpretasi' dan 'rekomendasi'
     */
    private function buatInterpretasi(array $rule): array
    {
        $antecedent = $rule['antecedent'];
        $consequent = $rule['consequent'];
        $support = $rule['support'] * 100;
        $confidence = $rule['confidence'] * 100;
        $lift = $rule['lift'];

        // Format interpretasi sebagai satu paragraf mengalir
        $interpretasi = sprintf(
            'Kombinasi produk %s dan %s memiliki nilai support sebesar %.2f%%, artinya %.2f%% dari seluruh transaksi pada periode ini mengandung kedua produk tersebut secara bersamaan. Nilai confidence sebesar %.2f%% menunjukkan bahwa dari transaksi yang mengandung %s, sebesar %.2f%% di antaranya juga mengandung %s.',
            $antecedent,
            $consequent,
            $support,
            $support,
            $confidence,
            $antecedent,
            $confidence,
            $consequent
        );

        // Format rekomendasi dengan logika bertingkat berdasarkan lift
        if ($lift > 2) {
            $rekomendasi = sprintf(
                'Dengan nilai lift sebesar %.3f (jauh di atas 1), kombinasi ini menunjukkan hubungan asosiasi yang KUAT. Sangat direkomendasikan sebagai kandidat utama strategi bundling atau penempatan produk berdekatan.',
                $lift
            );
        } elseif ($lift > 1 && $lift <= 2) {
            $rekomendasi = sprintf(
                'Dengan nilai lift sebesar %.3f (di atas 1), kombinasi ini menunjukkan hubungan asosiasi positif dengan kekuatan SEDANG. Dapat dipertimbangkan sebagai kandidat strategi bundling atau promosi, meski keterkaitannya tidak sekuat kombinasi lain dengan nilai lift lebih tinggi.',
                $lift
            );
        } else {
            $rekomendasi = sprintf(
                'Dengan nilai lift sebesar %.3f (tidak lebih besar dari 1), kombinasi ini TIDAK menunjukkan hubungan asosiasi yang signifikan. Kombinasi ini TIDAK direkomendasikan sebagai dasar strategi bundling.',
                $lift
            );
        }

        return [
            'interpretasi' => $interpretasi,
            'rekomendasi' => $rekomendasi,
        ];
    }

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

        // Tambahkan interpretasi dan rekomendasi untuk setiap rule
        foreach ($topRules as $key => $rule) {
            $interpretasiData = $this->buatInterpretasi($rule);
            $topRules[$key]['interpretasi'] = $interpretasiData['interpretasi'];
            $topRules[$key]['rekomendasi'] = $interpretasiData['rekomendasi'];
        }

        // Label konteks periode untuk section interpretasi
        $labelPeriodeInterpretasi = $selectedRun
            ? $selectedRun->nama_file_upload . ' (' . ($selectedRun->periode_awal?->format('d M Y') ?? '-')
              . ' - ' . ($selectedRun->periode_akhir?->format('d M Y') ?? '-') . ')'
            : 'Semua Periode (Gabungan)';

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
            'topRules',
            'labelPeriodeInterpretasi'
        ));
    }
}
