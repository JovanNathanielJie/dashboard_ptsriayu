<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Kelola Akun Admin Penjualan</h1>
                    <p class="mt-2 text-sm text-slate-600">Daftar akun Admin Penjualan yang terdaftar di sistem.</p>
                </div>
                <a href="{{ route('users.create') }}"
                   class="inline-flex items-center rounded-xl bg-[#F4C76F] px-5 py-3 text-sm font-semibold text-[#1F2A2D] transition hover:bg-[#e9ba5d]">
                    + Tambah Akun
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Nama</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Email</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Terdaftar Sejak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adminPenjualanList as $admin)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $admin->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $admin->email }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $admin->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">
                                    Belum ada akun Admin Penjualan yang ditambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
