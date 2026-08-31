<!-- Topbar Header Fixed -->
<nav class="fixed inset-x-0 top-0 z-50 h-16 w-full border-b border-[#EADCC7] bg-[#11181B] text-white shadow-sm">
    <div class="flex h-full w-full items-center justify-between px-4 sm:px-6">
        <!-- Sisi Kiri: Hamburger + Logo -->
        <div class="flex items-center gap-4">
            <button type="button" @click="sidebarOpen = !sidebarOpen" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/90 transition hover:bg-white/10 focus:outline-none" aria-label="Toggle Sidebar">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#F4C76F] text-sm font-bold text-[#11181B]">
                    S
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-[#F4C76F]">SISTEM ANALISIS</p>
                    <p class="text-sm font-medium text-white">Dashboard</p>
                </div>
            </div>
        </div>

        <!-- Sisi Kanan: Profile Dropdown -->
        <div class="flex items-center">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-sm font-medium text-white/90 transition hover:bg-white/10 focus:outline-none">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#F4C76F] text-xs font-bold text-[#11181B]">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span>{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 fill-current text-white/70" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-3 py-2">
                        <div class="mb-2 border-b border-slate-200 pb-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#A1582F]">Akun</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Edit Profil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Logout') }}
                            </x-dropdown-link>
                        </form>
                    </div>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</nav>

<!-- Overlay saat Sidebar terbuka -->
<div x-show="sidebarOpen"
     @click="sidebarOpen = false"
     x-cloak
     class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm transition-opacity">
</div>

<!-- Sidebar Off-Canvas (z-index dibuat z-50 agar tampil di atas segalanya) -->
<aside x-show="sidebarOpen"
       x-cloak
       x-transition:enter="transition ease-out duration-200 transform"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="fixed left-0 top-0 z-50 h-full w-[260px] border-r border-[#d8d1c5] bg-[#f3f0eb] text-slate-800 shadow-2xl">

    <div class="flex h-16 items-center gap-3 bg-[#1c5d9f] px-5 text-white">
        <div class="flex h-9 w-9 items-center justify-center rounded-full border border-white/30 bg-white/10 text-base font-bold">
            ★
        </div>
        <div class="text-lg font-bold">Sistem Analisis</div>
    </div>

    <div class="space-y-4 p-4">
        <div>
            <p class="mb-3 px-2 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">Menu</p>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition {{ request()->routeIs('dashboard') ? 'bg-[#dfeafc] text-[#1d3c6e] shadow-sm ring-1 ring-[#c9d8f4]' : 'hover:bg-slate-200/60' }}">
                <span>🏠</span>
                <span>Dashboard</span>
            </a>

            @if (Auth::user()->isAdminPenjualan())
                <a href="{{ route('upload.create') }}" class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition {{ request()->routeIs('upload.create') ? 'bg-[#dfeafc] text-[#1d3c6e] shadow-sm ring-1 ring-[#c9d8f4]' : 'hover:bg-slate-200/60' }}">
                    <span>📤</span>
                    <span>Upload Data</span>
                </a>
            @endif

            <a href="{{ route('profile.edit') }}" class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition {{ request()->routeIs('profile.edit') ? 'bg-[#dfeafc] text-[#1d3c6e] shadow-sm ring-1 ring-[#c9d8f4]' : 'hover:bg-slate-200/60' }}">
                <span>👤</span>
                <span>Profil Pengguna</span>
            </a>
        </div>

        <div class="border-t border-slate-300/60 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-200/60">
                    <span>🚪</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
