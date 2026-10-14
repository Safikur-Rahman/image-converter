<x-app-layout title="Usage">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-900">Usage</h1>
        <p class="mt-0.5 text-sm text-slate-500">Track your monthly conversions and storage.</p>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Conversions this month</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($conversionsThisMonth) }} <span class="text-sm text-slate-400">/ {{ $limit === -1 ? 'unlimited' : number_format($limit) }}</span></p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Background removals</p>
                <p class="mt-1 text-2xl font-bold text-violet-600">{{ number_format($backgroundRemovalsThisMonth) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Downloads</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($downloadsThisMonth) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Storage used</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ round($storageBytes / 1048576, 2) }} <span class="text-sm text-slate-400">MB</span></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Monthly activity ({{ $currentPeriod }})</h2>
                <span class="text-xs text-slate-400">{{ $remaining }} {{ $limit === -1 ? '' : 'remaining' }}</span>
            </div>
            <div class="card-body">
                @php
                    $max = collect($monthly)->max('conversions') ?: 1;
                @endphp

                <div class="flex items-end gap-3 h-40">
                    @foreach ($monthly as $m)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs font-semibold text-slate-600">{{ $m['conversions'] }}</span>
                            <div class="w-full rounded-t-md bg-indigo-500 transition-all" style="height: {{ max(2, ($m['conversions'] / $max) * 100) }}%"></div>
                            <span class="text-[11px] text-slate-400">{{ \Carbon\Carbon::createFromFormat('Y-m', $m['month'])->format('M') }}</span>
                        </div>
                    @endforeach
                </div>

                @if ($limit > 0)
                    <div class="mt-6">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Plan limit: {{ number_format($limit) }} conversions / month</span>
                            <span>{{ round(($conversionsThisMonth / $limit) * 100, 1) }}% used</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-indigo-600" style="width: {{ min(100, ($conversionsThisMonth / $limit) * 100) }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>