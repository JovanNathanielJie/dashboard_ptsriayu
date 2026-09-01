<?php

namespace App\Http\Controllers;

use App\Models\AnalysisRun;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        // Ambil SEMUA analysis_runs (tidak hanya yang status='done')
        // Load relasi 'user' dan urutkan terbaru
        $runs = AnalysisRun::with('user')
            ->latest()
            ->paginate(15);

        return view('riwayat.index', compact('runs'));
    }
}
