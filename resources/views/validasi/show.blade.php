<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Tinjau Data Transaksi</h1>
                    <p class="mt-2 text-sm text-slate-600">{{ $run->nama_file_upload }}</p>
                </div>
                <a href="{{ route('validasi.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Kembali ke Antrian</a>
            </div>

            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Periode</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $run->periode_awal?->format('d M Y') }} - {{ $run->periode_akhir?->format('d M Y') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Diunggah Oleh</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $run->user->name ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Faktur Unik</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $run->total_faktur_unik ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">Baris Bersih</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $run->total_baris_clean ?? 0 }}</p>
                </div>
            </div>

            <div class="mb-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-800">Contoh 20 Baris Pertama</p>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-white text-left">
                            <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Nomor Faktur</th>
                            <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Tanggal</th>
                            <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Nama Barang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sampelTransaksi as $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2 font-mono text-slate-700">{{ $item->nomor_faktur }}</td>
                                <td class="px-4 py-2 font-mono text-slate-700">{{ $item->tanggal }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $item->nama_barang }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <form method="POST" action="{{ route('validasi.approve', $run) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                    @csrf
                    <label class="mb-2 block text-sm font-medium text-emerald-900">Setujui Data Ini</label>
                    <textarea name="catatan" rows="3" placeholder="Catatan (opsional)" class="w-full rounded-lg border-emerald-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    <button type="submit" class="mt-4 w-full rounded-xl bg-[#2C6A5C] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#245a4e]">
                        Setujui & Lanjutkan
                    </button>
                </form>

                <form method="POST" action="{{ route('validasi.reject', $run) }}" class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm" onsubmit="return confirm('Yakin ingin menolak data ini? Admin Penjualan harus mengunggah ulang dari awal.');">
                    @csrf
                    <label class="mb-2 block text-sm font-medium text-red-900">Tolak Data Ini</label>
                    <textarea name="catatan" rows="3" required placeholder="Alasan penolakan (wajib diisi)" class="w-full rounded-lg border-red-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                    @error('catatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="mt-4 w-full rounded-xl bg-[#C1584A] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#a8483f]">
                        Tolak Data Ini
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
