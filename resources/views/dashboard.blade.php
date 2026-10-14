<x-app-layout title="Dashboard">

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">Here's what's happening with your conversions.</p>
            </div>
            <a href="{{ route('converter') }}" class="btn btn-primary">
                <x-icon name="converter" class="h-4 w-4" />
                New Conversion
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Stats cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Conversions</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['total']) }}</p>
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
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Processing</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($stats['processing']) }}</p>
                </div>
                <div class="rounded-lg bg-amber-100 p-3 text-amber-600"><x-icon name="clock" class="h-6 w-6" /></div>
            </div>

            <div class="card p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Failed</p>
                    <p class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($stats['failed']) }}</p>
                </div>
                <div class="rounded-lg bg-rose-100 p-3 text-rose-600"><x-icon name="alert" class="h-6 w-6" /></div>
            </div>
        </div>

        <!-- Usage summary -->
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Monthly Usage</h2>
                <a href="{{ route('usage') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View details &rarr;</a>
            </div>
            <div class="card-body">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-3xl font-bold text-slate-900">{{ $monthly['svg_conversion'] ?? 0 }} <span class="text-base font-medium text-slate-400">/ {{ $limits->conversionsLeftText(auth()->user()) }}</span></p>
                        <p class="text-xs text-slate-500">conversions used this month</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase text-slate-400">Storage used</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ round($stats['storage_bytes'] / 1048576, 2) }} MB</p>
                    </div>
                </div>

                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    @php
                        $limit = auth()->user()->effectivePlan()->monthly_conversion_limit;
                        $used = $monthly['svg_conversion'] ?? 0;
                        $pct = $limit > 0 ? min(100, round($used / $limit * 100)) : 0;
                    @endphp
                    <div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Recent conversions -->
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Recent Conversions</h2>
                <a href="{{ route('conversions.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View all &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">File</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recent as $conversion)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $conversion->original_filename }}</td>
                                <td class="px-5 py-3">
                                    <span class="badge badge-slate uppercase">{{ $conversion->conversion_type === 'b_w' ? 'B&W' : 'Color' }}</span>
                                </td>
                                <td class="px-5 py-3"><x-status-badge :status="$conversion->status" /></td>
                                <td class="px-5 py-3 text-slate-500">{{ $conversion->created_at->diffForHumans() }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('conversions.show', $conversion->uuid) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                                    No conversions yet.
                                    <a href="{{ route('converter') }}" class="ml-1 text-indigo-600 font-semibold">Start converting</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>