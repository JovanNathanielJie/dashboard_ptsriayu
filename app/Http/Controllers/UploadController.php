<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\TransaksiItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function create()
    {
        return view('upload.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'excel_file.required' => 'File Excel wajib diunggah.',
            'excel_file.file' => 'File yang diunggah harus berupa file Excel.',
            'excel_file.mimes' => 'Format file harus .xlsx atau .xls.',
            'excel_file.max' => 'Ukuran file terlalu besar. Maksimal 10 MB.',
        ]);

        $file = $validated['excel_file'];
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('uploads', $originalName);
        $fullPath = Storage::path($path);

        $analysisRun = AnalysisRun::create([
            'user_id' => auth()->id(),
            'nama_file_upload' => $originalName,
            'periode_awal' => null,
            'periode_akhir' => null,
            'status' => 'uploaded',
        ]);

        try {
            $worksheet = Excel::load($fullPath)->get();

            if ($worksheet->isEmpty()) {
                throw ValidationException::withMessages([
                    'excel_file' => 'File Excel tidak berisi data. Pastikan file laporan transaksi Accurate Online yang valid.',
                ]);
            }

            $currentFaktur = null;
            $currentTanggal = null;
            $foundNomorFaktur = false;
            $parsedItems = [];

            foreach ($worksheet as $row) {
                $label = trim((string) ($row['D'] ?? $row[3] ?? ''));
                $value = trim((string) ($row['G'] ?? $row[6] ?? ''));
                $kodeBarang = trim((string) ($row['C'] ?? $row[2] ?? ''));
                $namaBarang = trim((string) ($row['H'] ?? $row[7] ?? ''));
                $kuantitas = trim((string) ($row['L'] ?? $row[11] ?? ''));

                if ($label === 'Nomor #') {
                    $currentFaktur = $value !== '' ? $value : null;
                    $foundNomorFaktur = true;
                    continue;
                }

                if ($label === 'Tanggal') {
                    if ($value !== '') {
                        $currentTanggal = Carbon::parse($value);
                    }
                    continue;
                }

                if ($currentFaktur !== null && $kodeBarang !== '' && $namaBarang !== '' && $kuantitas !== '') {
                    $parsedItems[] = [
                        'analysis_run_id' => $analysisRun->id,
                        'nomor_faktur' => $currentFaktur,
                        'tanggal' => $currentTanggal ? $currentTanggal->toDateString() : null,
                        'nama_barang' => $namaBarang,
                    ];
                }
            }

            if (! $foundNomorFaktur) {
                throw ValidationException::withMessages([
                    'excel_file' => 'File yang Anda unggah bukan format ekspor Accurate Online yang valid. Label "Nomor #" tidak ditemukan.',
                ]);
            }

            if (empty($parsedItems)) {
                throw ValidationException::withMessages([
                    'excel_file' => 'Tidak ada item transaksi yang berhasil diproses. Pastikan file berisi blok faktur dengan item barang yang valid.',
                ]);
            }

            foreach ($parsedItems as $item) {
                TransaksiItem::create($item);
            }

            $tanggalList = TransaksiItem::where('analysis_run_id', $analysisRun->id)
                ->pluck('tanggal')
                ->filter()
                ->map(fn ($date) => Carbon::parse($date));

            if ($tanggalList->isNotEmpty()) {
                $analysisRun->periode_awal = $tanggalList->min()->toDateString();
                $analysisRun->periode_akhir = $tanggalList->max()->toDateString();
            }

            $analysisRun->total_baris_raw = count($parsedItems);
            $analysisRun->save();

            return redirect()->route('upload.create')->with('success', 'File transaksi berhasil diunggah dan diproses. Total item tersimpan: '.count($parsedItems).'.');
        } catch (ValidationException $e) {
            $analysisRun->status = 'failed';
            $analysisRun->save();

            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            $analysisRun->status = 'failed';
            $analysisRun->save();

            return back()->withErrors([
                'excel_file' => 'Gagal memproses file Excel: '.$e->getMessage(),
            ])->withInput();
        }
    }
}
