<aside class="hidden lg:flex w-64 shrink-0 border-r border-slate-200 bg-white flex-col" aria-label="Sidebar">
    <div class="flex items-center gap-2.5 px-6 h-16 border-b border-slate-100">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm">IC</div>
        <a href="{{ route('dashboard') }}" class="text-base font-bold tracking-tight text-slate-900">
            {{ config('app.name') }}
        </a>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-4 overflow-y-auto" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <span class="flex items-center gap-3">
                <x-icon name="home" class="h-4 w-4" />
                Dashboard
            </span>
        </a>

        <a href="{{ route('converter') }}" class="nav-link {{ request()->routeIs('converter') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <span class="flex items-center gap-3">
                <x-icon name="converter" class="h-4 w-4" />
                Converter
            </span>
        </a>

        <a href="{{ route('conversions.index') }}" class="nav-link {{ request()->routeIs('conversions.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <span class="flex items-center gap-3">
                <x-icon name="history" class="h-4 w-4" />
                My Conversions
            </span>
        </a>

        <a href="{{ route('usage') }}" class="nav-link {{ request()->routeIs('usage') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <span class="flex items-center gap-3">
                <x-icon name="chart" class="h-4 w-4" />
                Usage
            </span>
        </a>

        <a href="{{ route('subscription.index') }}" class="nav-link {{ request()->routeIs('subscription.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <span class="flex items-center gap-3">
                <x-icon name="sparkles" class="h-4 w-4" />
                Subscription
            </span>
        </a>

        <div class="pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400 px-3">
            Account
        </div>

        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <span class="flex items-center gap-3">
                <x-icon name="user" class="h-4 w-4" />
                Profile
            </span>
        </a>
    </nav>

    @auth
        @if (auth()->user()->isAdmin())
            <div class="pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400 px-3">
                Admin
            </div>
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="chart" class="h-4 w-4" />
                    Overview
                </span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="users" class="h-4 w-4" />
                    Users
                </span>
            </a>
            <a href="{{ route('admin.conversions.index') }}" class="nav-link {{ request()->routeIs('admin.conversions.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="file" class="h-4 w-4" />
                    Conversions
                </span>
            </a>
            <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="credit-card" class="h-4 w-4" />
                    Plans
                </span>
            </a>
            <a href="{{ route('admin.subscriptions.index') }}" class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Subscriptions
                </span>
            </a>
            <a href="{{ route('admin.background-removals.index') }}" class="nav-link {{ request()->routeIs('admin.background-removals.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Background Removals
                </span>
            </a>
            <a href="{{ route('admin.usage.index') }}" class="nav-link {{ request()->routeIs('admin.usage.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="chart" class="h-4 w-4" />
                    Usage
                </span>
            </a>
            <a href="{{ route('admin.logs.index') }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="bell" class="h-4 w-4" />
                    System Logs
                </span>
            </a>
            <a href="{{ route('admin.failed-jobs.index') }}" class="nav-link {{ request()->routeIs('admin.failed-jobs.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="alert" class="h-4 w-4" />
                    Failed Jobs
                </span>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                <span class="flex items-center gap-3">
                    <x-icon name="settings" class="h-4 w-4" />
                    Settings
                </span>
            </a>
        @endif
    @endauth

    <div class="border-t border-slate-100 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">
                {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-800">{{ auth()->user()?->name }}</p>
                <p class="truncate text-xs text-slate-400">{{ ucfirst(auth()->user()->effectivePlan()->name) }} plan</p>
            </div>
        </div>
    </div>
</aside>