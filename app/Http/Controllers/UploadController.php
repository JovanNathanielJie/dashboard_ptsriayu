<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTransactionUpload;
use App\Models\AnalysisRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            'periode_awal' => ['required', 'date'],
            'periode_akhir' => ['required', 'date', 'after_or_equal:periode_awal'],
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'periode_awal.required' => 'Periode awal wajib diisi.',
            'periode_awal.date' => 'Periode awal harus berupa tanggal yang valid.',
            'periode_akhir.required' => 'Periode akhir wajib diisi.',
            'periode_akhir.date' => 'Periode akhir harus berupa tanggal yang valid.',
            'periode_akhir.after_or_equal' => 'Periode akhir harus sama atau setelah periode awal.',
            'excel_file.required' => 'File Excel wajib diunggah.',
            'excel_file.file' => 'File yang diunggah harus berupa file Excel.',
            'excel_file.mimes' => 'Format file harus .xlsx atau .xls.',
            'excel_file.max' => 'Ukuran file terlalu besar. Maksimal 10 MB.',
        ]);

        $userId = Auth::id();

        if (! $userId) {
            return back()->withErrors([
                'excel_file' => 'Sesi Anda telah berakhir. Silakan login kembali.',
            ])->withInput();
        }

        $file = $validated['excel_file'];
        $originalName = $file->getClientOriginalName();
        $uniqueName = now()->format('Ymd_His') . '_' . uniqid() . '_' . $originalName;
        $path = $file->storeAs('uploads', $uniqueName);
        $fullPath = Storage::path($path);

        $analysisRun = AnalysisRun::create([
            'user_id' => $userId,
            'nama_file_upload' => $originalName,
            'periode_awal' => $validated['periode_awal'],
            'periode_akhir' => $validated['periode_akhir'],
            'status' => 'uploaded',
        ]);

        try {
            $analysisRun->update(['status' => 'queued']);

            ProcessTransactionUpload::dispatch(
                $analysisRun->id,
                $fullPath,
                $validated['periode_awal'],
                $validated['periode_akhir'],
            );

            return redirect()->route('upload.create')->with(
                'success',
                'File berhasil masuk antrian proses. Upload per bulan diproses di background agar data besar tetap aman dari timeout.'
            );
        } catch (\Throwable $e) {
            $analysisRun->update(['status' => 'failed']);

            return back()->withErrors([
                'excel_file' => 'Gagal menyiapkan proses unggah: ' . $e->getMessage(),
            ])->withInput();
        }
    }
}
