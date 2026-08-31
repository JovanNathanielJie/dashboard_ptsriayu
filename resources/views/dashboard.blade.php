<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#A1582F]">PT SRI AYU</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Dashboard Utama</h2>
            </div>
            <span class="inline-flex items-center rounded-full border border-[#F0D7A7] bg-[#FFF6E7] px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#7B4B2A]">
                {{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}
            </span>
        </div>
    </x-slot>

    <div class="bg-[#F7F5F0] py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 rounded-3xl border border-[#F0D7A7] bg-gradient-to-r from-[#1F2A2D] via-[#1B2427] to-[#11181B] p-6 text-white shadow-sm sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.28em] text-[#F4C76F]">Selamat datang</p>
                        <h3 class="mt-3 text-3xl font-bold tracking-tight">Halo, {{ Auth::user()->name }}.</h3>
                        <p class="mt-3 max-w-2xl text-sm text-slate-300">
                            Pantau performa penjualan, kelola data transaksi, dan lihat pola pembelian yang relevan untuk pengambilan keputusan bisnis Anda.
                        </p>
                    </div>

                    @if (Auth::user()->isAdminPenjualan())
                        <a href="{{ route('upload.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#F4C76F] px-5 py-3 text-sm font-semibold text-[#1F2A2D] transition hover:bg-[#e9ba5d]">
                            + Unggah Data Transaksi
                        </a>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Analisis</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">12</p>
                    <p class="mt-2 text-xs text-emerald-600">+3 dari bulan lalu</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Transaksi Diproses</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">1.248</p>
                    <p class="mt-2 text-xs text-sky-600">Data terbaru tersedia</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Periode Aktif</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">2026</p>
                    <p class="mt-2 text-xs text-amber-600">Filter aktif</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Status Sistem</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">Normal</p>
                    <p class="mt-2 text-xs text-emerald-600">Semua modul berjalan</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1.5fr_0.9fr]">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-slate-900">Ringkasan Aktivitas</h4>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">7 hari terakhir</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                <span>Data transaksi masuk</span>
                                <span>82%</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-[82%] rounded-full bg-[#A1582F]"></div>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                <span>Pola pembelian terdeteksi</span>
                                <span>64%</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-[64%] rounded-full bg-[#F4C76F]"></div>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                <span>Ketersediaan data gudang</span>
                                <span>91%</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-[91%] rounded-full bg-[#2F8F74]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h4 class="text-lg font-semibold text-slate-900">Menu Cepat</h4>
                    <div class="mt-5 space-y-3">
                        @if (Auth::user()->isAdminPenjualan())
                            <a href="{{ route('upload.create') }}" class="flex items-center justify-between rounded-xl border border-[#F0D7A7] bg-[#FFF8EE] px-4 py-3 text-sm font-medium text-[#7B4B2A] transition hover:bg-[#fff1d8]">
                                <span>Unggah Data Transaksi</span>
                                <span>→</span>
                            </a>
                        @endif

                        <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                            <span>Dashboard Overview</span>
                            <span>→</span>
                        </a>

                        <a href="{{ route('profile.edit') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                            <span>Profil Pengguna</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
