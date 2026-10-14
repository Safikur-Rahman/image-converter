<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white/80 px-6 backdrop-blur">
    <div class="flex items-center gap-3">
        <!-- Mobile menu trigger -->
        <button type="button" class="lg:hidden rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="mobileOpen = !mobileOpen" aria-label="Toggle navigation" aria-expanded="false">
            <x-icon name="menu" class="h-5 w-5" />
        </button>
        <x-icon name="logo" class="h-7 w-7 lg:hidden" />
    </div>

    @auth
        <div class="hidden md:flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600">
            <x-icon name="sparkles" class="h-3.5 w-3.5 text-indigo-500" />
            <span>
                <strong class="font-semibold text-slate-800">{{ $limits?->conversionsLeftText(auth()->user()) ?? '—' }}</strong>
                conversions left
            </span>
        </div>
    @endauth

    <div class="flex items-center gap-3" x-data="{ open: false }">
        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="logout" class="h-4 w-4" />
                Logout
            </button>
        </form>

        <div class="relative">
            <button type="button" @click="open = !open" class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700" aria-label="Account menu">
                {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}
            </button>

            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" x-cloak>
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Profile &amp; Settings</a>
                <a href="{{ route('subscription.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Subscription</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Admin Panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">Logout</button>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Mobile nav drawer -->
<div x-show="mobileOpen" x-cloak x-transition class="fixed inset-0 z-30 lg:hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/40" @click="mobileOpen = false"></div>
    <div class="absolute top-0 left-0 h-full w-64 bg-white shadow-xl p-4">
        <div class="flex items-center justify-between mb-4">
            <span class="font-bold text-slate-900">{{ config('app.name') }}</span>
            <button type="button" @click="mobileOpen = false" class="rounded-md p-1 text-slate-400 hover:bg-slate-100" aria-label="Close menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>
        <nav class="space-y-1">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : 'nav-link-inactive' }}">Dashboard</a>
            <a href="{{ route('converter') }}" class="nav-link {{ request()->routeIs('converter') ? 'nav-link-active' : 'nav-link-inactive' }}">Converter</a>
            <a href="{{ route('conversions.index') }}" class="nav-link {{ request()->routeIs('conversions.*') ? 'nav-link-active' : 'nav-link-inactive' }}">My Conversions</a>
            <a href="{{ route('usage') }}" class="nav-link {{ request()->routeIs('usage') ? 'nav-link-active' : 'nav-link-inactive' }}">Usage</a>
            <a href="{{ route('subscription.index') }}" class="nav-link {{ request()->routeIs('subscription.*') ? 'nav-link-active' : 'nav-link-inactive' }}">Subscription</a>
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'nav-link-active' : 'nav-link-inactive' }}">Profile</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="nav-link nav-link-active">Admin Panel</a>
            @endif
        </nav>
    </div>
</div>