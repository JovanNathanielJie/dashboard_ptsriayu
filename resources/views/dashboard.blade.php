<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <!-- 1. Banner Utama Selamat Datang -->
            <div class="mb-8 rounded-3xl border border-[#F0D7A7] bg-gradient-to-r from-[#1F2A2D] via-[#1B2427] to-[#11181B] p-6 text-white shadow-sm sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.28em] text-[#F4C76F]">Selamat datang</p>
                        <h3 class="mt-3 text-3xl font-bold tracking-tight">Halo, {{ Auth::user()->name }}.</h3>
                        <p class="mt-3 max-w-2xl text-sm text-slate-300">
                            Pantau performa penjualan, kelola data transaksi, dan lihat pola pembelian yang relevan untuk pengambilan keputusan bisnis Anda.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if (Auth::user()->isAdminPenjualan())
                            <a href="{{ route('upload.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#F4C76F] px-5 py-3 text-sm font-semibold text-[#1F2A2D] transition hover:bg-[#e9ba5d]">
                                + Unggah Data Transaksi
                            </a>

                            @php $targetRun = $latestRun ?? $allRuns->first(); @endphp
                            @if ($targetRun)
                                <!-- BAGIAN B: Tombol Ubah Parameter (bg-accent) -->
                                <a href="{{ route('analysis.parameter', $targetRun) }}" class="inline-flex items-center justify-center rounded-xl bg-[#C1584A] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#a8483f]">
                                    ⚙️ Ubah Parameter Terbaru
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Ringkasan Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <!-- Total Analisis -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Analisis</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">{{ $totalAnalisis }}</p>
                    <p class="mt-2 text-xs text-emerald-600">
                        @if ($totalAnalisis > 0)
                            Data tersedia di sistem
                        @else
                            Belum ada data
                        @endif
                    </p>
                </div>

                <!-- Transaksi Diproses -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Transaksi Diproses</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">{{ number_format($totalTransaksi) }}</p>
                    <p class="mt-2 text-xs text-sky-600">
                        @if ($totalTransaksi > 0)
                            Data terbaru tersedia
                        @else
                            Belum ada transaksi
                        @endif
                    </p>
                </div>

                <!-- Periode Aktif -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Periode Aktif</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">{{ $periodeAktif }}</p>
                    <p class="mt-2 text-xs text-amber-600">
                        @if ($totalAnalisis > 0)
                            Filter aktif
                        @else
                            Belum ada periode
                        @endif
                    </p>
                </div>

                <!-- Status Sistem -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Status Sistem</p>
                    <p class="mt-4 text-3xl font-bold text-slate-900">{{ $statusSistem }}</p>
                    <p class="mt-2 text-xs text-emerald-600">
                        @if ($statusSistem === 'Normal')
                            Semua modul berjalan
                        @else
                            Menunggu data upload
                        @endif
                    </p>
                </div>
            </div>

            <!-- 3. Section Bawah: Chart Visualisasi & Menu Cepat -->
            <div class="mt-8 space-y-6">
                <!-- BAGIAN C: Chart Visualisasi 10 Association Rules dengan Lift Tertinggi -->
                @if (count($topRules) > 0)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                            <div>
                                <h4 class="text-lg font-semibold text-slate-900">
                                    Aturan Asosiasi Teratas {{ $selectedRunId === 'all' ? '(Semua Periode)' : '(Top 10)' }}
                                </h4>
                                <p class="text-sm text-slate-500 mt-1">Berdasarkan nilai lift tertinggi</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-4">
                                <!-- Filter Dropdown Periode / Analysis Run -->
                                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                    <label for="run_id" class="text-xs font-semibold uppercase tracking-wider text-slate-600">Periode:</label>
                                    <select name="run_id" id="run_id" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary shadow-sm py-1.5 px-3 font-medium">
                                        <!-- Opsi ALL (Gabungan) -->
                                        <option value="all" {{ $selectedRunId === 'all' ? 'selected' : '' }}>
                                            🌐 Semua Periode (Gabungan)
                                        </option>

                                        <!-- Opsi Per Analysis Run -->
                                        @foreach ($allRuns as $run)
                                            <option value="{{ $run->id }}" {{ $selectedRunId == $run->id ? 'selected' : '' }}>
                                                {{ $run->nama_file_upload }} ({{ $run->periode_awal?->format('M Y') ?? '-' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>

                                @if (Auth::user()->isAdminPenjualan())
                                    @php $cardRun = $latestRun ?? $allRuns->first(); @endphp
                                    @if ($cardRun)
                                        <a href="{{ route('analysis.parameter', $cardRun) }}" class="text-sm font-medium text-[#C1584A] hover:underline">
                                            Edit Parameter →
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Chart Container -->
                        <div class="mb-8 relative h-80 w-full">
                            <canvas id="rulesChart"></canvas>
                        </div>

                        <!-- Data Table -->
                        <div class="overflow-x-auto border-t border-slate-200 pt-6">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                        <th class="px-4 py-3">Antecedent → Consequent</th>
                                        <th class="px-4 py-3 text-right">Support</th>
                                        <th class="px-4 py-3 text-right">Confidence</th>
                                        <th class="px-4 py-3 text-right">Lift</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topRules as $rule)
                                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                                            <td class="px-4 py-3">
                                                <span class="font-mono text-[11px] text-slate-700" title="{{ $rule['label'] }}">
                                                    {{ \Illuminate\Support\Str::limit($rule['antecedent'], 30) }} → {{ \Illuminate\Support\Str::limit($rule['consequent'], 20) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ number_format($rule['support'] * 100, 2) }}%</td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ number_format($rule['confidence'] * 100, 2) }}%</td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-[#2F6F62]">{{ number_format($rule['lift'], 3) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm flex items-center justify-between">
                        <p class="text-sm text-amber-800">
                            📊 Belum ada aturan asosiasi yang ditemukan pada analisis ini. Coba ubah parameter analisis untuk hasil yang berbeda.
                        </p>
                        @if(isset($allRuns) && count($allRuns) > 0)
                            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                <label for="run_id" class="text-xs font-semibold uppercase tracking-wider text-amber-800">Pilih Periode Lain:</label>
                                <select name="run_id" id="run_id" onchange="this.form.submit()" class="rounded-xl border-amber-300 text-sm focus:border-primary focus:ring-primary shadow-sm py-1.5 px-3">
                                    <option value="all" {{ $selectedRunId === 'all' ? 'selected' : '' }}>
                                        🌐 Semua Periode (Gabungan)
                                    </option>
                                    @foreach ($allRuns as $run)
                                        <option value="{{ $run->id }}" {{ $selectedRunId == $run->id ? 'selected' : '' }}>
                                            {{ $run->nama_file_upload }} ({{ $run->periode_awal?->format('M Y') ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
                @endif

                <!-- Layout: Ringkasan Aktivitas & Menu Cepat -->
                <div class="grid gap-6 lg:grid-cols-[1.5fr_0.9fr]">
                    <!-- Ringkasan Aktivitas -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-semibold text-slate-900">Ringkasan Aktivitas</h4>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">7 hari terakhir</span>
                        </div>

                        <div class="mt-6 space-y-4">
                            @foreach ($activityMetrics as $metric)
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                        <span>{{ $metric['label'] }}</span>
                                        <span>{{ $metric['value'] }}%</span>
                                    </div>
                                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $metric['color'] }}" style="width: {{ $metric['value'] }}%"></div>
                                    </div>
                                    <p class="mt-2 text-[11px] text-slate-500">{{ $metric['detail'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Menu Cepat -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h4 class="text-lg font-semibold text-slate-900">Menu Cepat</h4>
                        <div class="mt-5 space-y-3">
                            @if (Auth::user()->isAdminPenjualan())
                                <a href="{{ route('upload.create') }}" class="flex items-center justify-between rounded-xl border border-[#F0D7A7] bg-[#FFF8EE] px-4 py-3 text-sm font-medium text-[#7B4B2A] transition hover:bg-[#fff1d8]">
                                    <span>Unggah Data Transaksi + Analisis</span>
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
    </div>

    <!-- Chart.js Script untuk Visualisasi Aturan Asosiasi -->
    @if (count($topRules) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Persiapkan data untuk Chart.js
                const topRules = @json($topRules);

                // Warna gradient dari primary (#2F6F62) ke link (#C1584A)
                const labels = topRules.map(rule => rule.label.length > 40 ? rule.label.substring(0, 37) + '...' : rule.label);
                const liftValues = topRules.map(rule => rule.lift);
                const maxLift = Math.max(...liftValues);

                // Buat gradient warna berdasarkan lift value
                const colors = liftValues.map(lift => {
                    const ratio = lift / maxLift;
                    // Interpolasi dari #2F6F62 (primary) ke #C1584A (link)
                    const r = Math.round(47 + (193 - 47) * ratio);
                    const g = Math.round(111 + (88 - 111) * ratio);
                    const b = Math.round(98 + (74 - 98) * ratio);
                    return `rgb(${r}, ${g}, ${b})`;
                });

                const ctx = document.getElementById('rulesChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Lift',
                            data: liftValues,
                            backgroundColor: colors,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Properti ini yang membuat tampilan grafik menjadi horizontal
                        responsive: true,
                        maintainAspectRatio: false, // Disarankan false agar tinggi canvas fleksibel
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const rule = topRules[context.dataIndex];
                                        return [
                                            'Support: ' + (rule.support * 100).toFixed(2) + '%',
                                            'Confidence: ' + (rule.confidence * 100).toFixed(2) + '%'
                                        ];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
