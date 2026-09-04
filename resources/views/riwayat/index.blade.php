<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <!-- Judul Halaman -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Hasil Analisis</h1>
                <p class="mt-2 text-sm text-slate-600">Lihat dan kelola semua analisis transaksi yang telah dilakukan</p>
            </div>

            <!-- Tabel Riwayat -->
            @if ($runs->total() > 0)
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-left">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">No</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Nama File</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Periode</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Diunggah Oleh</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Tanggal</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Faktur/Baris</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Hasil Analisis</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($runs as $index => $run)
                                @php
                                    // Hitung nomor urut berdasarkan pagination
                                    $no = ($runs->currentPage() - 1) * $runs->perPage() + $index + 1;

                                    // Tentukan status badge (DISESUAIKAN DENGAN ALUR VALIDASI)
                                    if ($run->status === 'done' && $run->total_frequent_itemsets) {
                                        $statusBadge = ['class' => 'bg-emerald-100 text-emerald-800', 'text' => 'Selesai Dianalisis'];
                                    } elseif ($run->status === 'disetujui') {
                                        $statusBadge = ['class' => 'bg-[#FFF3E0] text-[#E68D28]', 'text' => 'Disetujui, Menunggu Analisis'];
                                    } elseif ($run->status === 'menunggu_validasi') {
                                        $statusBadge = ['class' => 'bg-blue-100 text-blue-800', 'text' => 'Menunggu Validasi'];
                                    } elseif ($run->status === 'ditolak') {
                                        $statusBadge = ['class' => 'bg-[#FADBD8] text-[#C1584A]', 'text' => 'Ditolak'];
                                    } elseif ($run->status === 'processing') {
                                        $statusBadge = ['class' => 'bg-[#F5E6D3] text-[#C1584A]', 'text' => 'Sedang Diproses'];
                                    } elseif ($run->status === 'failed') {
                                        $statusBadge = ['class' => 'bg-[#FADBD8] text-[#C1584A]', 'text' => 'Gagal'];
                                    } else {
                                        $statusBadge = ['class' => 'bg-slate-100 text-slate-700', 'text' => ucfirst($run->status)];
                                    }

                                    // Format Periode
                                    $periodText = $run->periode_awal && $run->periode_akhir
                                        ? $run->periode_awal->format('d M Y') . ' - ' . $run->periode_akhir->format('d M Y')
                                        : '-';

                                    // Format Faktur/Baris
                                    $fakturBarisText = ($run->total_faktur_unik || $run->total_baris_clean)
                                        ? ($run->total_faktur_unik ?? 0) . ' faktur / ' . ($run->total_baris_clean ?? 0) . ' baris'
                                        : '-';

                                    // Format Hasil Analisis
                                    $hasilAnalisisText = $run->total_frequent_itemsets
                                        ? ($run->total_frequent_itemsets ?? 0) . ' itemset, ' . ($run->total_association_rules ?? 0) . ' rules'
                                        : 'Belum dianalisis';
                                @endphp
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-700">{{ $no }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-700 font-medium">{{ $run->nama_file_upload }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-700">{{ $periodText }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-700">{{ $run->user->name ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-700">{{ $run->created_at->format('d M Y H:i') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadge['class'] }}">
                                            {{ $statusBadge['text'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-slate-700">{{ $fakturBarisText }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-slate-700">{{ $hasilAnalisisText }}</span>
                                    </td>

                                    <!-- LOGIKA KOLOM AKSI DISESUAIKAN -->
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($run->total_frequent_itemsets)
                                                <!-- Jika sudah analisis selesai -->
                                                <a href="{{ route('dashboard', ['run_id' => $run->id]) }}"
                                                   class="inline-flex items-center justify-center rounded-lg bg-[#2F6F62] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#255550]">
                                                    Lihat Detail
                                                </a>
                                            @elseif ($run->status === 'menunggu_validasi')
                                                <!-- Jika menunggu validasi, hanya Direktur Utama yang bertindak -->
                                                @if (Auth::user()->isDirekturUtama())
                                                    <a href="{{ route('validasi.show', $run) }}" class="inline-flex items-center rounded-lg bg-[#2C6A5C] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#245a4e]">
                                                        Tinjau & Validasi
                                                    </a>
                                                @else
                                                    <span class="text-xs text-slate-500">Menunggu validasi</span>
                                                @endif
                                            @elseif ($run->status === 'disetujui')
                                                <!-- Jika disetujui, hanya Admin Penjualan yang bisa proses parameter -->
                                                @if (Auth::user()->isAdminPenjualan())
                                                    <a href="{{ route('analysis.parameter', $run) }}" class="inline-flex items-center rounded-lg bg-[#C1584A] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#a8483f]">
                                                        ⚙️ Atur Parameter
                                                    </a>
                                                @else
                                                    <span class="text-xs text-slate-500">-</span>
                                                @endif
                                                @elseif ($run->status === 'ditolak')
                                                    <!-- Jika Ditolak, ubah jadi button agar muncul popup saat diklik -->
                                                    <button type="button"
                                                            onclick="alert('Alasan Penolakan:\n\n{{ addslashes($run->catatan_validasi ?? 'Ditolak tanpa catatan') }}')"
                                                            class="text-xs font-semibold text-[#C1584A] cursor-pointer border-b border-dashed border-[#C1584A] hover:text-red-800 transition">
                                                        Lihat Catatan
                                                    </button>
                                                @else
                                                <span class="text-xs text-slate-500">-</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $runs->links() }}
                </div>
            @else
                <!-- Pesan Kosong -->
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
                    <p class="text-lg font-semibold text-amber-900">📊 Belum ada riwayat analisis</p>
                    <p class="mt-2 text-sm text-amber-800">
                        Admin Penjualan dapat memulai dengan mengunggah data transaksi baru.
                    </p>
                    @if (Auth::user()->isAdminPenjualan())
                        <a href="{{ route('upload.create') }}" class="mt-4 inline-flex items-center rounded-xl bg-[#F4C76F] px-5 py-3 text-sm font-semibold text-[#1F2A2D] transition hover:bg-[#e9ba5d]">
                            + Unggah Data Transaksi
                        </a>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
