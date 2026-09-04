<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Tambahkan baris ini

class ValidasiController extends Controller
{
    public function index()
    {
        $runs = AnalysisRun::with('user')
            ->where('status', 'menunggu_validasi')
            ->oldest()
            ->get();

        return view('validasi.index', compact('runs'));
    }

    public function show(AnalysisRun $run)
    {
        $sampelTransaksi = $run->transaksiItems()->limit(20)->get();
        return view('validasi.show', compact('run', 'sampelTransaksi'));
    }

    public function approve(Request $request, AnalysisRun $run)
    {
        if ($run->status !== 'menunggu_validasi') abort(409, 'Data ini sudah tidak dalam status menunggu validasi.');

        $validated = $request->validate(['catatan' => ['nullable', 'string']]);

        $run->update([
            'status' => 'disetujui',
            'validated_by' => Auth::id(), // Gunakan Facade Auth
            'validated_at' => now(),
            'catatan_validasi' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('validasi.index')->with('success', "Data transaksi \"{$run->nama_file_upload}\" telah disetujui.");
    }

    public function reject(Request $request, AnalysisRun $run)
    {
        if ($run->status !== 'menunggu_validasi') abort(409, 'Data ini sudah tidak dalam status menunggu validasi.');

        $validated = $request->validate(
            ['catatan' => ['required', 'string', 'min:10']],
            ['catatan.required' => 'Alasan penolakan wajib diisi.', 'catatan.min' => 'Alasan penolakan minimal 10 karakter.']
        );

        $run->update([
            'status' => 'ditolak',
            'validated_by' => Auth::id(), // Gunakan Facade Auth
            'validated_at' => now(),
            'catatan_validasi' => $validated['catatan'],
        ]);

        return redirect()->route('validasi.index')->with('success', "Data transaksi \"{$run->nama_file_upload}\" telah ditolak.");
    }
}
