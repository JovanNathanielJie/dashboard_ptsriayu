<x-app-layout>
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="mt-2 text-2xl font-bold text-slate-900">
                Atur Parameter & Jalankan Analisis
            </h1>
            <p class="text-slate-600">
                Tentukan parameter algoritma Apriori untuk menganalisis pola pembelian dari data transaksi Anda
            </p>
        </div>

        <!-- Summary Card -->
        <div class="bg-paper rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
            <h2 class="font-semibold text-lg text-slate-900 mb-4">Ringkasan Data</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Nama File -->
                <div class="bg-slate-50 rounded p-4">
                    <p class="text-xs text-slate-600 uppercase tracking-wider mb-1">File</p>
                    <p class="font-semibold text-slate-900 truncate" title="{{ $run->nama_file_upload }}">
                        {{ Str::limit($run->nama_file_upload, 20) }}
                    </p>
                </div>

                <!-- Periode -->
                <div class="bg-slate-50 rounded p-4">
                    <p class="text-xs text-slate-600 uppercase tracking-wider mb-1">Periode</p>
                    <p class="font-semibold text-slate-900">
                        {{ $run->periode_awal?->format('M d') ?? '-' }} - {{ $run->periode_akhir?->format('M d, Y') ?? '-' }}
                    </p>
                </div>

                <!-- Total Baris Clean -->
                <div class="bg-slate-50 rounded p-4">
                    <p class="text-xs text-slate-600 uppercase tracking-wider mb-1">Baris Transaksi</p>
                    <p class="font-semibold text-slate-900">
                        {{ number_format($run->total_baris_clean ?? 0) }}
                    </p>
                </div>

                <!-- Faktur Unik -->
                <div class="bg-slate-50 rounded p-4">
                    <p class="text-xs text-slate-600 uppercase tracking-wider mb-1">Faktur Unik</p>
                    <p class="font-semibold text-slate-900">
                        {{ number_format($run->total_faktur_unik ?? 0) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Error Alert -->
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="font-semibold text-red-900">Terjadi Kesalahan</h3>
                        <ul class="mt-2 text-sm text-red-800">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form -->
        <form
            action="{{ route('analysis.process', $run) }}"
            method="POST"
            x-data="{ isSubmitting: false }"
            @submit="isSubmitting = true"
            class="bg-paper rounded-lg shadow-sm border border-slate-200 p-8"
        >
            @csrf

            <div class="space-y-8">
                <!-- Min Support -->
                <div>
                    <label for="min_support" class="block font-semibold text-slate-900 mb-2">
                        Minimum Support <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="min_support"
                        name="min_support"
                        step="0.01"
                        min="0.01"
                        max="1"
                        value="{{ old('min_support', 0.10) }}"
                        required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />

                    <p class="mt-2 text-sm text-slate-600">
                        <strong>Rentang:</strong> 0.01 - 1.00 (default: 0.10)
                        <br>
                        <strong>Penjelasan:</strong> Ambang batas minimum kemunculan kombinasi produk agar dianggap sering muncul. Semakin kecil nilainya, semakin banyak kombinasi yang ditemukan, tetapi proses analisis akan lebih lambat.
                    </p>
                </div>

                <!-- Max Len -->
                <div>
                    <label for="max_len" class="block font-semibold text-slate-900 mb-2">
                        Panjang Maksimum Itemset <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="max_len"
                        name="max_len"
                        step="1"
                        min="1"
                        max="5"
                        value="{{ old('max_len', 2) }}"
                        required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />

                    <p class="mt-2 text-sm text-slate-600">
                        <strong>Rentang:</strong> 1 - 5 (default: 2)
                        <br>
                        <strong>Penjelasan:</strong> Jumlah maksimal produk dalam satu kombinasi yang akan dicari. Contoh: max_len=2 akan mencari kombinasi 2 produk, max_len=3 akan mencari hingga 3 produk, dst.
                    </p>
                </div>

                <!-- Min Confidence -->
                <div>
                    <label for="min_confidence" class="block font-semibold text-slate-900 mb-2">
                        Minimum Confidence <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="min_confidence"
                        name="min_confidence"
                        step="0.01"
                        min="0.01"
                        max="1"
                        value="{{ old('min_confidence', 0.60) }}"
                        required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />

                    <p class="mt-2 text-sm text-slate-600">
                        <strong>Rentang:</strong> 0.01 - 1.00 (default: 0.60)
                        <br>
                        <strong>Penjelasan:</strong> Ambang batas kepercayaan aturan asosiasi. Menunjukkan seberapa sering produk di sisi kanan muncul ketika produk di sisi kiri dibeli (confidence = 60% berarti dari 10 pembelian produk A, 6 diantaranya juga membeli produk B).
                    </p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 mt-8 pt-8 border-t border-slate-200">
                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="px-6 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <svg
                        x-show="isSubmitting"
                        class="w-4 h-4 animate-spin"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="isSubmitting ? 'Memproses...' : 'Jalankan Analisis'"></span>
                </button>

                <a
                    href="{{ route('upload.create') }}"
                    class="px-6 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition"
                >
                    Kembali
                </a>
            </div>

            <!-- Loading State Message -->
            <div
                x-show="isSubmitting"
                class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm"
            >
                ⏳ Analisis sedang berjalan. Proses ini mungkin memakan waktu beberapa detik hingga puluhan detik tergantung jumlah data transaksi. Mohon tunggu...
            </div>
        </form>
    </div>
</x-app-layout>
