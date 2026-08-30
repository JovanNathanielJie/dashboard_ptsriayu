<x-guest-layout>
    <div class="min-h-screen flex font-sans">

        {{-- PANEL KIRI: Brand, hanya tampil di layar besar --}}
        <div class="hidden lg:flex lg:w-1/2 bg-ink relative overflow-hidden items-center justify-center p-12">
            {{-- Motif garis penghubung, elemen signature --}}
            <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 400 600" fill="none">
                <circle cx="80" cy="120" r="4" fill="#E2A33D" />
                <circle cx="220" cy="80" r="3" fill="#F7F5F0" />
                <circle cx="320" cy="200" r="4" fill="#C1584A" />
                <circle cx="140" cy="320" r="3" fill="#F7F5F0" />
                <circle cx="300" cy="420" r="4" fill="#E2A33D" />
                <circle cx="90" cy="480" r="3" fill="#F7F5F0" />
                <path d="M80,120 L220,80 L320,200 L140,320 L300,420 L90,480" stroke="#F7F5F0" stroke-width="1" stroke-dasharray="4 6" />
            </svg>

            <div class="relative z-10 text-paper max-w-sm">
                <p class="font-mono text-xs tracking-widest text-accent uppercase mb-4">Dashboard Apriori</p>
                <h1 class="font-display text-4xl leading-tight mb-4">
                    Menemukan pola di balik setiap transaksi.
                </h1>
                <p class="text-paper/70 text-sm leading-relaxed">
                    Sistem analisis pola pembelian produk PT Sriayu Citra Mandiri,
                    berbasis algoritma Apriori.
                </p>
            </div>
        </div>

        {{-- PANEL KANAN: Form login --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center bg-paper p-8">
            <div class="w-full max-w-sm">
                <h2 class="font-display text-2xl text-ink mb-1">Masuk ke akun Anda</h2>
                <p class="text-muted text-sm mb-8">Silakan masuk sesuai peran Anda di sistem.</p>

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
                        class="w-full bg-primary hover:bg-primary-dark text-paper font-medium py-2.5 rounded-lg transition-colors">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
