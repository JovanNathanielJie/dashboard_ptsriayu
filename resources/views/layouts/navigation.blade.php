<nav x-data="{ open: false }" class="border-b border-[#EADCC7] bg-[#11181B] text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#F4C76F] text-sm font-bold text-[#11181B]">
                        S
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-[#F4C76F]">PT SRI AYU</p>
                        <p class="text-sm font-medium text-white">Dashboard</p>
                    </div>
                </a>

                <div class="hidden items-center gap-1 sm:-my-px sm:ms-6 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-white/80 hover:text-white">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (Auth::user()->isAdminPenjualan())
                        <x-nav-link :href="route('upload.create')" :active="request()->routeIs('upload.create')" class="text-white/80 hover:text-white">
                            {{ __('Upload Data') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/5 px-2 py-2 text-sm font-medium text-white/90 transition hover:bg-white/10 focus:outline-none">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F4C76F] text-sm font-bold text-[#11181B]">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden lg:block">{{ Auth::user()->name }}</span>
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

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Logout') }}
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-white/80 hover:bg-white/5 hover:text-white focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/10 bg-[#11181B] sm:hidden">
        <div class="space-y-1 px-2 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-white/80 hover:text-white">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (Auth::user()->isAdminPenjualan())
                <x-responsive-nav-link :href="route('upload.create')" :active="request()->routeIs('upload.create')" class="text-white/80 hover:text-white">
                    {{ __('Upload Data') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-white/10 px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F4C76F] text-sm font-bold text-[#11181B]">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
                <div>
                    <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-white/60">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="text-white/80 hover:text-white">
                    {{ __('Edit Profil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();" class="text-white/80 hover:text-white">
                        {{ __('Logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
