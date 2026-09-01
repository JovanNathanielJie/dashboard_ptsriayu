<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\TransaksiItem;
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
        $validated = $request->validate([
            'periode_awal'  => ['required', 'date'],
            'periode_akhir' => ['required', 'date', 'after_or_equal:periode_awal'],
            'excel_file'    => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'periode_awal.required'         => 'Periode awal wajib diisi.',
            'periode_awal.date'             => 'Periode awal harus berupa tanggal yang valid.',
            'periode_akhir.required'        => 'Periode akhir wajib diisi.',
            'periode_akhir.date'            => 'Periode akhir harus berupa tanggal yang valid.',
            'periode_akhir.after_or_equal'  => 'Periode akhir harus sama atau setelah periode awal.',
            'excel_file.required'           => 'File Excel wajib diunggah.',
            'excel_file.file'               => 'File yang diunggah harus berupa file Excel.',
            'excel_file.mimes'              => 'Format file harus .xlsx atau .xls.',
            'excel_file.max'                => 'Ukuran file terlalu besar. Maksimal 10 MB.',
        ]);

        $userId = Auth::id();
        if (! $userId) {
            return back()->withErrors([
                'excel_file' => 'Sesi Anda telah berakhir. Silakan login kembali.',
            ])->withInput();
        }

        $file = $validated['excel_file'];
        $originalName = $file->getClientOriginalName();

        $analysisRun = AnalysisRun::create([
            'user_id'          => $userId,
            'nama_file_upload' => $originalName,
            'periode_awal'     => $validated['periode_awal'],
            'periode_akhir'    => $validated['periode_akhir'],
            'status'           => 'uploaded',
        ]);

        try {
            $analysisRun->update(['status' => 'processing']);

            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $originalName)
                ->post(config('services.python_api.url') . '/parse-excel', [
                    'periode_awal'  => $validated['periode_awal'],
                    'periode_akhir' => $validated['periode_akhir'],
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
