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
            'periode_awal'  => ['required', 'date'],
            'periode_akhir' => ['required', 'date', 'after_or_equal:periode_awal'],
            'excel_file'    => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'periode_awal.required'  => 'Periode awal wajib diisi.',
            'periode_awal.date'      => 'Periode awal harus berupa tanggal yang valid.',
            'periode_akhir.required' => 'Periode akhir wajib diisi.',
            'periode_akhir.date'     => 'Periode akhir harus berupa tanggal yang valid.',
            'periode_akhir.after_or_equal' => 'Periode akhir harus sama atau setelah periode awal.',
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

        $periodeAwal = $validated['periode_awal'];
        $periodeAkhir = $validated['periode_akhir'];

        // 3. Simpan File ke Storage Internal dengan nama unik
        $file = $validated['excel_file'];
        $originalName = $file->getClientOriginalName();
        $uniqueName = now()->format('Ymd_His') . '_' . uniqid() . '_' . $originalName;
        $path = $file->storeAs('uploads', $uniqueName);
        $fullPath = Storage::path($path);

        // 4. Inisialisasi Record AnalysisRun dengan Status Early ('uploaded')
        $analysisRun = AnalysisRun::create([
            'user_id'          => $userId,
            'nama_file_upload' => $originalName,
            'periode_awal'     => $periodeAwal,
            'periode_akhir'    => $periodeAkhir,
            'status'           => 'uploaded',
        ]);

        try {
            // Ubah status ke 'processing' saat pembacaan file dimulai
            $analysisRun->update(['status' => 'processing']);

            // Membaca file Excel versi 3.x (API resmi)
            $sheets = Excel::toCollection(null, $fullPath);
            $rows = $sheets->first();

            if ($rows === null || $rows->isEmpty()) {
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
                $kolomD = $row->get(3);
                $kolomG = $row->get(6);
                $kolomC = $row->get(2);
                $kolomH = $row->get(7);
                $kolomL = $row->get(11);

                $label      = trim((string) ($kolomD ?? ''));
                $value      = trim((string) ($kolomG ?? ''));
                $kodeBarang = trim((string) ($kolomC ?? ''));
                $namaBarang = trim((string) ($kolomH ?? ''));
                $kuantitasRaw = $kolomL;
                $isKuantitasValid = is_numeric($kuantitasRaw);

                if ($label === 'Nomor #') {
                    $currentFaktur = $value !== '' ? $value : null;
                    $foundNomorFaktur = true;
                    continue;
                }

                if ($label === 'Tanggal') {
                    if ($kolomG instanceof \DateTimeInterface) {
                        $currentTanggal = Carbon::instance($kolomG)->toDateString();
                    } elseif (is_numeric($kolomG)) {
                        $currentTanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($kolomG)->format('Y-m-d');
                    } elseif (! empty($kolomG)) {
                        try {
                            $currentTanggal = Carbon::parse((string) $kolomG)->toDateString();
                        } catch (\Exception $e) {
                            $currentTanggal = null;
                        }
                    }
                    continue;
                }

                $dalamRentang = $currentTanggal !== null
                    && $currentTanggal >= $periodeAwal
                    && $currentTanggal <= $periodeAkhir;

                if ($currentFaktur !== null && $kodeBarang !== '' && $namaBarang !== '' && $isKuantitasValid && $dalamRentang) {
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
                    'excel_file' => 'Tidak ditemukan transaksi pada rentang tanggal ' . $periodeAwal . ' sampai ' . $periodeAkhir . ' di dalam file yang diunggah. Periksa kembali rentang tanggal atau file yang dipilih.',
                ]);
            }

            // 7. Bulk Insert dengan Chunking (Efisiensi Performa Database)
            foreach (array_chunk($parsedItems, 500) as $chunk) {
                TransaksiItem::insert($chunk);
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
