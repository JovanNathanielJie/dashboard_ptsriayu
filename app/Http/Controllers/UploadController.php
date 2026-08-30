<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use App\Models\TransaksiItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // 1. Validasi Input File Excel
        $validated = $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'excel_file.required' => 'File Excel wajib diunggah.',
            'excel_file.file'     => 'File yang diunggah harus berupa file Excel.',
            'excel_file.mimes'    => 'Format file harus .xlsx atau .xls.',
            'excel_file.max'      => 'Ukuran file terlalu besar. Maksimal 10 MB.',
        ]);

        // 2. Keamanan Autentikasi User
        $userId = Auth::id();

        if (! $userId) {
            return back()->withErrors([
                'excel_file' => 'Sesi Anda telah berakhir. Silakan login kembali.',
            ])->withInput();
        }

        // 3. Simpan File ke Storage Internal
        $file = $validated['excel_file'];
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('uploads', $originalName);
        $fullPath = Storage::path($path);

        // 4. Inisialisasi Record AnalysisRun dengan Status Early ('uploaded')
        $analysisRun = AnalysisRun::create([
            'user_id'          => $userId,
            'nama_file_upload' => $originalName,
            'periode_awal'     => null,
            'periode_akhir'    => null,
            'status'           => 'uploaded',
        ]);

        try {
            // Ubah status ke 'processing' saat pembacaan file dimulai
            $analysisRun->update(['status' => 'processing']);

            // Membaca file Excel
            $worksheet = Excel::load($fullPath)->get();

            // Mengambil sheet pertama secara aman
            $rows = $worksheet->first() instanceof \Illuminate\Support\Collection
                ? $worksheet->first()
                : $worksheet;

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages([
                    'excel_file' => 'File Excel tidak berisi data. Pastikan file laporan transaksi Accurate Online yang valid.',
                ]);
            }

            $currentFaktur = null;
            $currentTanggal = null;
            $foundNomorFaktur = false;
            $parsedItems = [];
            $now = now();

            // 5. Iterasi Baris Data Ekspor Accurate Online
            foreach ($rows as $row) {
                $label      = trim((string) ($row['D'] ?? $row[3] ?? ''));
                $value      = trim((string) ($row['G'] ?? $row[6] ?? ''));
                $kodeBarang = trim((string) ($row['C'] ?? $row[2] ?? ''));
                $namaBarang = trim((string) ($row['H'] ?? $row[7] ?? ''));
                $kuantitas  = trim((string) ($row['L'] ?? $row[11] ?? ''));

                if ($label === 'Nomor #') {
                    $currentFaktur = $value !== '' ? $value : null;
                    $foundNomorFaktur = true;
                    continue;
                }

                if ($label === 'Tanggal') {
                    if ($value !== '') {
                        try {
                            $currentTanggal = Carbon::parse($value)->toDateString();
                        } catch (\Exception $e) {
                            $currentTanggal = null;
                        }
                    }
                    continue;
                }

                if ($currentFaktur !== null && $kodeBarang !== '' && $namaBarang !== '' && $kuantitas !== '') {
                    $parsedItems[] = [
                        'analysis_run_id' => $analysisRun->id,
                        'nomor_faktur'    => $currentFaktur,
                        'tanggal'         => $currentTanggal,
                        'nama_barang'     => $namaBarang,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            // 6. Validasi Hasil Parsing
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

            // 7. Bulk Insert dengan Chunking (Efisiensi Performa Database)
            foreach (array_chunk($parsedItems, 500) as $chunk) {
                TransaksiItem::insert($chunk);
            }

            // 8. Hitung Periode Transaksi Awal dan Akhir
            $dates = array_values(array_filter(array_column($parsedItems, 'tanggal')));
            if (! empty($dates)) {
                $analysisRun->periode_awal = min($dates);
                $analysisRun->periode_akhir = max($dates);
            }

            // 9. Simpan Status Akhir ('done')
            $analysisRun->total_baris_raw = count($parsedItems);
            $analysisRun->status = 'done';
            $analysisRun->save();

            return redirect()->route('upload.create')->with(
                'success',
                'File transaksi berhasil diunggah dan diproses. Total item tersimpan: ' . count($parsedItems) . '.'
            );

        } catch (ValidationException $e) {
            $analysisRun->update(['status' => 'failed']);

            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            $analysisRun->update(['status' => 'failed']);

            return back()->withErrors([
                'excel_file' => 'Gagal memproses file Excel: ' . $e->getMessage(),
            ])->withInput();
        }
    }
}
