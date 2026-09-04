<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Validasi Data Transaksi</h1>
                <p class="mt-2 text-sm text-slate-600">Tinjau dan setujui data transaksi yang diunggah Admin Penjualan sebelum dapat dianalisis.</p>
            </div>
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($runs->count() > 0)
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-left">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">No</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Nama File</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Periode</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Diunggah Oleh</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Tanggal Upload</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Faktur/Baris</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($runs as $index => $run)
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-700">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $run->nama_file_upload }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $run->periode_awal?->format('d M Y') }} - {{ $run->periode_akhir?->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $run->user->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $run->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-700">{{ $run->total_faktur_unik ?? 0 }} faktur / {{ $run->total_baris_clean ?? 0 }} baris</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('validasi.show', $run) }}" class="inline-flex items-center rounded-lg bg-[#2C6A5C] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#245a4e]">
                                            Tinjau & Validasi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <p class="text-slate-600">Tidak ada data transaksi yang menunggu validasi saat ini.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
