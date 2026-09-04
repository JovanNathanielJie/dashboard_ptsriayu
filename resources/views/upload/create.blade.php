<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#A1582F]">Admin Penjualan</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Unggah Data Transaksi</h2>
            </div>
            <span class="inline-flex items-center rounded-full border border-[#F0D7A7] bg-[#FFF6E7] px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#7B4B2A]">
                {{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}
            </span>
        </div>
    </x-slot>

    <div class="bg-[#F7F5F0] py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-[#F0D7A7] bg-white p-6 shadow-sm sm:p-8">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-2xl border border-[#E7DAB8] bg-[#FFFDF9] p-5 sm:p-6">
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900">Upload File Excel per Bulan</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Unggah data transaksi per bulan agar proses tetap ringan dan aman untuk file yang besar. Setelah semua bulan selesai, data akan digabungkan untuk analisis.
                        </p>

                        <form id="uploadForm" action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
                            @csrf

                            <!-- Mengganti 2 Input Tanggal menjadi 1 Input Bulan -->
                            <div>
                                <label for="periode_bulan" class="mb-2 block text-sm font-medium text-slate-700">Pilih Bulan & Tahun Transaksi</label>
                                <input id="periode_bulan" name="periode_bulan" type="month" value="{{ old('periode_bulan') }}" required class="w-full rounded-xl border border-[#E7DAB8] bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-[#C8922B] focus:outline-none focus:ring-2 focus:ring-[#F4C76F]/20">
                            </div>

                            <div class="rounded-2xl border-2 border-dashed border-[#B4C9C5] bg-[#F3F8F7] p-6 transition hover:border-[#2B6F6A] hover:bg-[#edf6f4]">
                                <label for="excel_file" class="block cursor-pointer">
                                    <div class="flex flex-col items-center justify-center gap-4 text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#1F2A2D] text-white shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16.5v-1.5A2.5 2.5 0 0 1 9.5 12.5H14.5A2.5 2.5 0 0 1 17 15v1.5M12 3v10m0 0 3-3m-3 3-3-3"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-base font-semibold text-slate-900">Klik untuk pilih file atau seret file ke sini</p>
                                            <p class="mt-1 text-sm text-slate-500">Format yang didukung: .xlsx, .xls (maks. 10 MB)</p>
                                        </div>
                                    </div>
                                    <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls" class="sr-only" required>
                                </label>
                            </div>

                            <div id="fileNameBox" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                File terpilih: <span id="fileNameText" class="font-medium text-slate-900"></span>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#2C6A5C] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#245a4e] focus:outline-none focus:ring-2 focus:ring-[#2C6A5C]/20 focus:ring-offset-2">
                                    Unggah & Parse Data
                                </button>
                                <span id="uploadStatus" class="hidden text-sm font-medium text-[#7B4B2A]">Memproses file...</span>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-[#E7DAB8] bg-[#FFF8EE] p-5 sm:p-6">
                        <h4 class="text-xl font-bold text-slate-900">Panduan unggah</h4>
                        <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                            <li>• Unggah file per bulan, misalnya April, Mei, Juni, dan seterusnya.</li>
                            <li>• File yang diunggah harus berasal dari laporan penjualan Accurate Online.</li>
                            <li>• Baris berisi label <span class="font-mono font-semibold text-slate-900">Nomor #</span> dan <span class="font-mono font-semibold text-slate-900">Tanggal</span></li>
                            <li>• Kolom <span class="font-mono font-semibold text-slate-900">C</span> dan <span class="font-mono font-semibold text-slate-900">H</span> berisi kode dan nama barang</li>
                            <li>• Kolom <span class="font-mono font-semibold text-slate-900">L</span> berisi kuantitas item</li>
                            <li>• Baris subtotal akan otomatis diabaikan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('excel_file');
        const fileNameBox = document.getElementById('fileNameBox');
        const fileNameText = document.getElementById('fileNameText');
        const uploadStatus = document.getElementById('uploadStatus');
        const uploadForm = document.getElementById('uploadForm');

        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                fileNameText.textContent = this.files[0].name;
                fileNameBox.classList.remove('hidden');
            }
        });

        uploadForm.addEventListener('submit', function () {
            uploadStatus.classList.remove('hidden');
            uploadStatus.textContent = 'Memproses file...';
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');
        });
    </script>
</x-app-layout>
