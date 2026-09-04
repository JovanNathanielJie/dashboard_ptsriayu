<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-lg px-4 sm:px-6 lg:px-8">

            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mb-2">Tambah Akun Admin Penjualan</h1>
            <p class="text-sm text-slate-600 mb-8">Akun baru akan otomatis memiliki peran (role) Admin Penjualan.</p>

            <form method="POST" action="{{ route('users.store') }}" class="space-y-5 rounded-2xl border border-[#E7DAB8] bg-[#FFFDF9] p-6 shadow-sm">
                @csrf

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="w-full rounded-xl border border-[#E7DAB8] bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-[#C8922B] focus:outline-none focus:ring-2 focus:ring-[#F4C76F]/20">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        class="w-full rounded-xl border border-[#E7DAB8] bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-[#C8922B] focus:outline-none focus:ring-2 focus:ring-[#F4C76F]/20">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Kata Sandi</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-xl border border-[#E7DAB8] bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-[#C8922B] focus:outline-none focus:ring-2 focus:ring-[#F4C76F]/20">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        class="w-full rounded-xl border border-[#E7DAB8] bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-[#C8922B] focus:outline-none focus:ring-2 focus:ring-[#F4C76F]/20">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="rounded-xl bg-[#2C6A5C] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#245a4e]">
                        Simpan Akun
                    </button>
                    <a href="{{ route('users.index') }}"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
