<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-primary-dark/70">Admin Penjualan</p>
                <h2 class="font-display text-3xl text-ink">Unggah Data Transaksi</h2>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-paper px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <div class="rounded-2xl border border-primary/20 bg-white p-6 shadow-sm sm:p-8">
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

                <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <div>
                        <h3 class="font-display text-2xl text-ink">Upload File Excel</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            File yang diunggah harus berasal dari laporan penjualan Accurate Online dengan format blok faktur berulang.
                        </p>

                        <form id="uploadForm" action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
                            @csrf

                            <div class="rounded-2xl border-2 border-dashed border-primary/40 bg-primary/5 p-6 transition hover:border-primary hover:bg-primary/10">
                                <label for="excel_file" class="block cursor-pointer">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16.5v-1.5A2.5 2.5 0 0 1 9.5 12.5H14.5A2.5 2.5 0 0 1 17 15v1.5M12 3v10m0 0 3-3m-3 3-3-3"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-base font-semibold text-ink">Klik untuk pilih file atau seret file ke sini</p>
                                            <p class="mt-1 text-sm text-slate-500">Format yang didukung: .xlsx, .xls (maks. 10 MB)</p>
                                        </div>
                                    </div>
                                    <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls" class="sr-only" required>
                                </label>
                            </div>

                            <div id="fileNameBox" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                File terpilih: <span id="fileNameText" class="font-medium text-ink"></span>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                    Unggah & Parse Data
                                </button>
                                <span id="uploadStatus" class="hidden text-sm text-primary-dark">Memproses file...</span>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-primary/15 bg-primary/5 p-5">
                        <h4 class="font-display text-xl text-ink">Format yang diterima</h4>
                        <ul class="mt-4 space-y-3 text-sm text-slate-700">
                            <li>• Baris berisi label <span class="font-mono font-semibold">Nomor #</span> dan <span class="font-mono font-semibold">Tanggal</span></li>
                            <li>• Kolom <span class="font-mono font-semibold">D</span> untuk label, kolom <span class="font-mono font-semibold">G</span> untuk nilai</li>
                            <li>• Kolom <span class="font-mono font-semibold">C</span> dan <span class="font-mono font-semibold">H</span> berisi kode dan nama barang</li>
                            <li>• Kolom <span class="font-mono font-semibold">L</span> berisi kuantitas item</li>
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
