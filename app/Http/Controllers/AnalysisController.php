<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\AssociationRule;
use App\Models\FrequentItemset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AnalysisController extends Controller
{
    /**
     * Helper untuk menampilkan pesan error berdasarkan status saat ini
     */
    private function pesanStatusBelumSiap(AnalysisRun $run): string
    {
        return match ($run->status) {
            'menunggu_validasi' => 'Data ini masih menunggu validasi Direktur Utama.',
            'ditolak' => 'Data ini ditolak oleh Direktur Utama' . ($run->catatan_validasi ? ": {$run->catatan_validasi}" : '.'),
            'uploaded', 'processing' => 'Data ini belum selesai diproses.',
            default => 'Data ini belum dapat dianalisis saat ini.',
        };
    }

    /**
     * Tampilkan form untuk mengatur parameter Apriori
     */
    public function parameter(AnalysisRun $run)
    {
        // GUARD: Pastikan status sudah disetujui (atau sudah pernah dianalisis sebelumnya untuk re-run)
        if ($run->status !== 'disetujui' && !$run->total_frequent_itemsets) {
            return redirect()->route('riwayat.index')
                ->with('error', $this->pesanStatusBelumSiap($run));
        }

        return view('analysis.parameter', compact('run'));
    }

    /**
     * Proses analisis Apriori berdasarkan parameter yang dikirim
     */
    public function process(Request $request, AnalysisRun $run)
    {
        // GUARD: Cegah proses jika data belum valid
        if ($run->status !== 'disetujui' && !$run->total_frequent_itemsets) {
            return redirect()->route('riwayat.index')
                ->with('error', $this->pesanStatusBelumSiap($run));
        }

        // 1. Validasi input parameter
        $validated = $request->validate([
            'min_support' => ['required', 'numeric', 'min:0.01', 'max:1'],
            'max_len' => ['required', 'integer', 'min:1', 'max:5'],
            'min_confidence' => ['required', 'numeric', 'min:0.01', 'max:1'],
        ], [
            'min_support.required' => 'Minimum Support wajib diisi.',
            'min_support.numeric' => 'Minimum Support harus berupa angka desimal.',
            'min_support.min' => 'Minimum Support minimal 0.01.',
            'min_support.max' => 'Minimum Support maksimal 1.00.',
            'max_len.required' => 'Panjang Maksimum Itemset wajib diisi.',
            'max_len.integer' => 'Panjang Maksimum Itemset harus berupa angka bulat.',
            'max_len.min' => 'Panjang Maksimum Itemset minimal 1.',
            'max_len.max' => 'Panjang Maksimum Itemset maksimal 5.',
            'min_confidence.required' => 'Minimum Confidence wajib diisi.',
            'min_confidence.numeric' => 'Minimum Confidence harus berupa angka desimal.',
            'min_confidence.min' => 'Minimum Confidence minimal 0.01.',
            'min_confidence.max' => 'Minimum Confidence maksimal 1.00.',
        ]);

        // 2. Update AnalysisRun dengan parameter dan set status ke processing
        $run->update([
            'min_support' => (float) $validated['min_support'],
            'max_len' => (int) $validated['max_len'],
            'min_confidence' => (float) $validated['min_confidence'],
            'status' => 'processing',
        ]);

        try {
            // 3. Ambil data transaksi dari run ini
            $items = $run->transaksiItems()
                ->select('nomor_faktur', 'nama_barang')
                ->get()
                ->map(function ($item) {
                    return [
                        'nomor_faktur' => $item->nomor_faktur,
                        'nama_barang' => $item->nama_barang,
                    ];
                })
                ->toArray();

            // Jika data kosong, lempar error
            if (empty($items)) {
                throw ValidationException::withMessages([
                    'analysis' => 'Tidak ada data transaksi untuk dianalisis pada riwayat ini.',
                ]);
            }

            // 4. Kirim request ke Python API /analyze
            $response = Http::timeout(120)
                ->post(config('services.python_api.url') . '/analyze', [
                    'items' => $items,
                    'min_support' => (float) $validated['min_support'],
                    'max_len' => (int) $validated['max_len'],
                    'min_confidence' => (float) $validated['min_confidence'],
                ]);

            // Jika response gagal, ambil detail error
            if ($response->failed()) {
                $errorDetail = $response->json('detail') ?? 'Gagal menjalankan analisis di layanan Python.';
                throw ValidationException::withMessages(['analysis' => $errorDetail]);
            }

            $result = $response->json();

            // 5. Hapus hasil analisis lama jika ada
            $run->frequentItemsets()->delete();
            $run->associationRules()->delete();

            // 6. Insert frequent itemsets
            $frequentItemsets = $result['frequent_itemsets'] ?? [];
            $frequentRows = array_map(function ($item) use ($run) {
                return [
                    'analysis_run_id' => $run->id,
                    'itemset' => $item['itemset'],
                    'length' => $item['length'],
                    'support' => (float) $item['support'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $frequentItemsets);

            foreach (array_chunk($frequentRows, 500) as $chunk) {
                FrequentItemset::insert($chunk);
            }

            // 7. Insert association rules
            $associationRules = $result['association_rules'] ?? [];
            $rulesRows = array_map(function ($rule) use ($run) {
                return [
                    'analysis_run_id' => $run->id,
                    'antecedent' => $rule['antecedent'],
                    'consequent' => $rule['consequent'],
                    'support' => (float) $rule['support'],
                    'confidence' => (float) $rule['confidence'],
                    'lift' => (float) $rule['lift'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $associationRules);

            foreach (array_chunk($rulesRows, 500) as $chunk) {
                AssociationRule::insert($chunk);
            }

            // 8. Update AnalysisRun dengan summary dan status done
            $run->update([
                'total_frequent_itemsets' => $result['total_frequent_itemsets'],
                'total_association_rules' => $result['total_association_rules'],
                'status' => 'done',
            ]);

            // 9. Redirect ke dashboard dengan pesan sukses
            return redirect()->route('dashboard', ['run_id' => $run->id])->with(
                'success',
                "Analisis selesai! Ditemukan {$result['total_frequent_itemsets']} frequent itemsets dan {$result['total_association_rules']} association rules."
            );

        } catch (ValidationException $e) {
            $run->update(['status' => 'failed']);
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            $run->update(['status' => 'failed']);
            return back()->withErrors([
                'analysis' => 'Gagal menjalankan analisis: ' . $e->getMessage(),
            ])->withInput();
        }
    }
}
