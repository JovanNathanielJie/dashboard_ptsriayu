<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\TransaksiItem;
use Carbon\Carbon; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function create()
    {
        return view('upload.create');
    }

    public function store(Request $request)
    {
        // 1. Ubah validasi menjadi format Y-m (Tahun-Bulan)
        $validated = $request->validate([
            'periode_bulan' => ['required', 'date_format:Y-m'],
            'excel_file'    => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'periode_bulan.required'    => 'Bulan periode transaksi wajib dipilih.',
            'periode_bulan.date_format' => 'Format bulan tidak valid.',
            'excel_file.required'       => 'File Excel wajib diunggah.',
            'excel_file.file'           => 'File yang diunggah harus berupa file Excel.',
            'excel_file.mimes'          => 'Format file harus .xlsx atau .xls.',
            'excel_file.max'            => 'Ukuran file terlalu besar. Maksimal 10 MB.',
        ]);

        $userId = Auth::id();
        if (! $userId) {
            return back()->withErrors([
                'excel_file' => 'Sesi Anda telah berakhir. Silakan login kembali.',
            ])->withInput();
        }

        // 2. Hitung Otomatis Tanggal Awal & Akhir menggunakan Carbon
        $date = Carbon::createFromFormat('Y-m', $validated['periode_bulan']);
        $periodeAwal = $date->copy()->startOfMonth()->format('Y-m-d');
        $periodeAkhir = $date->copy()->endOfMonth()->format('Y-m-d');

        $file = $validated['excel_file'];
        $originalName = $file->getClientOriginalName();

        // 3. Simpan ke database dengan tanggal yang sudah digenerate
        $analysisRun = AnalysisRun::create([
            'user_id'          => $userId,
            'nama_file_upload' => $originalName,
            'periode_awal'     => $periodeAwal,
            'periode_akhir'    => $periodeAkhir,
            'status'           => 'uploaded',
        ]);

        try {
            $analysisRun->update(['status' => 'processing']);

            // 4. Kirim ke Python API menggunakan tanggal hasil generate otomatis
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $originalName)
                ->post(config('services.python_api.url') . '/parse-excel', [
                    'periode_awal'  => $periodeAwal,
                    'periode_akhir' => $periodeAkhir,
                ]);

            if ($response->failed()) {
                $errorDetail = $response->json('detail') ?? 'Gagal memproses file di layanan analisis Python.';
                throw ValidationException::withMessages(['excel_file' => $errorDetail]);
            }

            $result = $response->json();
            $items = $result['items'];
            $summary = $result['summary'];

            $now = now();
            $rows = array_map(function ($item) use ($analysisRun, $now) {
                return [
                    'analysis_run_id' => $analysisRun->id,
                    'nomor_faktur'    => $item['nomor_faktur'],
                    'tanggal'         => $item['tanggal'],
                    'nama_barang'     => $item['nama_barang'],
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }, $items);

            foreach (array_chunk($rows, 500) as $chunk) {
                TransaksiItem::insert($chunk);
            }

            $analysisRun->update([
                'total_baris_raw'    => $summary['total_baris_raw'],
                'total_baris_clean'  => $summary['total_baris_clean'],
                'total_faktur_unik'  => $summary['total_faktur_unik'],
                'total_produk_unik'  => $summary['total_produk_unik'],
                'status'             => 'done',
            ]);

            return redirect()->route('analysis.parameter', $analysisRun)->with(
                'success',
                "File berhasil diproses. {$summary['total_baris_clean']} baris transaksi tersimpan dari {$summary['total_faktur_unik']} faktur. Silakan atur parameter Apriori untuk melanjutkan analisis."
            );

        } catch (ValidationException $e) {
            $analysisRun->update(['status' => 'failed']);
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            $analysisRun->update(['status' => 'failed']);
            return back()->withErrors([
                'excel_file' => 'Gagal memproses file: ' . $e->getMessage(),
            ])->withInput();
        }
    }
}
