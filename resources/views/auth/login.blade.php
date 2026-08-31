<x-guest-layout>
    <div class="min-h-screen flex font-sans">

        <div class="hidden lg:flex lg:w-1/2 bg-[#1d4d3d] relative overflow-hidden items-center justify-center p-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.08),transparent_25%),radial-gradient(circle_at_bottom_right,_rgba(148,197,127,0.16),transparent_30%)]"></div>

            <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 400 600" fill="none">
                <circle cx="80" cy="120" r="4" fill="#d7ebce" />
                <circle cx="220" cy="80" r="3" fill="#f7f5f0" />
                <circle cx="320" cy="200" r="4" fill="#c7e0b9" />
                <circle cx="140" cy="320" r="3" fill="#f7f5f0" />
                <circle cx="300" cy="420" r="4" fill="#d7ebce" />
                <circle cx="90" cy="480" r="3" fill="#f7f5f0" />
                <path d="M80,120 L220,80 L320,200 L140,320 L300,420 L90,480" stroke="#f7f5f0" stroke-width="1" stroke-dasharray="4 6" />
            </svg>

            <div class="relative z-10 text-paper max-w-sm">
                <p class="font-mono text-xs tracking-[0.25em] text-[#dfeecf] uppercase mb-4">SISTEM ANALISIS TRANSAKSI</p>
                <h1 class="font-display text-4xl leading-tight mb-4 text-[#f7f5f0]">
                    Dashboard Apriori<br>
                    untuk Analisis Transaksi.
                </h1>
                <p class="text-[#dfeecf]/80 text-sm leading-relaxed">
                    Sistem analisis pola pembelian produk berbasis algoritma Apriori,
                    untuk mendukung pengambilan keputusan penjualan dan inventori.
                </p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center bg-[#F7F5F0] p-8">
            <div class="w-full max-w-sm">
                <p class="mb-2 text-xs font-medium tracking-[0.22em] text-[#A1582F] uppercase">Login Akses</p>
                <h2 class="font-display text-2xl text-ink mb-1">Masuk ke akun Anda</h2>
                <p class="text-muted text-sm mb-8">Silakan masuk sesuai peran Anda.</p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" value="Email" class="text-ink font-medium text-sm" />
                        <x-text-input id="email" class="block mt-1 w-full rounded-lg border-muted/30 focus:border-primary focus:ring-primary"
                            type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Kata Sandi" class="text-ink font-medium text-sm" />
                        <x-text-input id="password" class="block mt-1 w-full rounded-lg border-muted/30 focus:border-primary focus:ring-primary"
                            type="password" name="password" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" name="remember"
                            class="rounded border-muted/30 text-primary focus:ring-primary">
                        <label for="remember_me" class="ms-2 text-sm text-muted">Ingat saya</label>
                    </div>

                    <button type="submit"
                        class="w-full bg-[#A1582F] hover:bg-[#8d4b2a] text-white font-medium py-2.5 rounded-lg transition-colors shadow-sm">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
