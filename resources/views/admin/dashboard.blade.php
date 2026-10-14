<x-app-layout title="Admin Overview">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Admin Overview</h1>
            <p class="mt-0.5 text-sm text-slate-500">Platform-wide statistics.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Users</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['users']) }}</p>
                </div>
                <div class="rounded-lg bg-slate-100 p-3 text-slate-600"><x-icon name="users" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Conversions</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['conversions']) }}</p>
                </div>
                <div class="rounded-lg bg-indigo-100 p-3 text-indigo-600"><x-icon name="file" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Completed</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($stats['completed']) }}</p>
                </div>
                <div class="rounded-lg bg-emerald-100 p-3 text-emerald-600"><x-icon name="check" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Failed</p>
                    <p class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($stats['failed']) }}</p>
                </div>
                <div class="rounded-lg bg-rose-100 p-3 text-rose-600"><x-icon name="alert" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Processing</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($stats['processing']) }}</p>
                </div>
                <div class="rounded-lg bg-amber-100 p-3 text-amber-600"><x-icon name="clock" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Background Removals</p>
                    <p class="mt-1 text-2xl font-bold text-violet-600">{{ number_format($stats['background_removals']) }}</p>
                </div>
                <div class="rounded-lg bg-violet-100 p-3 text-violet-600"><x-icon name="sparkles" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Downloads</p>
                    <p class="mt-1 text-2xl font-bold text-sky-600">{{ number_format($stats['downloads']) }}</p>
                </div>
                <div class="rounded-lg bg-sky-100 p-3 text-sky-600"><x-icon name="download" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Failed Jobs</p>
                    <p class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($stats['failed_jobs']) }}</p>
                </div>
                <div class="rounded-lg bg-rose-100 p-3 text-rose-600"><x-icon name="alert" class="h-6 w-6" /></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Storage Used</h2>
                <a href="{{ route('admin.usage.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View usage &rarr;</a>
            </div>
            <div class="card-body">
                <p class="text-3xl font-bold text-slate-900">{{ round($stats['storage_bytes'] / 1048576, 2) }} <span class="text-base font-medium text-slate-400">MB</span></p>
                <p class="mt-1 text-xs text-slate-500">across all conversion files</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Conversions (last 14 days)</h2></div>
            <div class="card-body">
                @if (count($dailyConversions))
                    <div class="flex items-end gap-2 h-40">
                        @foreach ($dailyConversions as $day => $count)
                            @php $max = max($dailyConversions); $pct = $max > 0 ? ($count / $max) * 100 : 0; @endphp
                            <div class="flex-1 flex flex-col items-center justify-end gap-1">
                                <span class="text-[10px] font-semibold text-slate-600">{{ $count }}</span>
                                <div class="w-full rounded-t bg-indigo-600" style="height: {{ max($pct, 2) }}%"></div>
                                <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($day)->format('d') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400">No conversions in the past 14 days.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Monthly Usage by Action</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($monthly as $action => $count)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $action }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ number_format($count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-5 py-10 text-center text-slate-400">No usage recorded this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
