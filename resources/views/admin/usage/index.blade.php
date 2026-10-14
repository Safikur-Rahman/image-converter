<x-app-layout title="Usage">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Usage</h1>
            <p class="mt-0.5 text-sm text-slate-500">Usage records and downloads.</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card" x-data="{}">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Usage Records</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $record->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $record->action }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $record->quantity }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $record->period }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $record->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No usage records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <div class="px-5 py-4 border-t border-slate-200">
                    {{ $records->links() }}
                </div>
            @endif
        </div>

        <div class="card" x-data="{}">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Downloads</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">IP Hash</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User Agent</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($downloads as $download)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <span class="font-medium text-slate-800">{{ $download->user?->name ?? '—' }}</span>
                                    <p class="text-xs text-slate-400">{{ $download->conversion?->uuid ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-3"><span class="badge badge-slate">{{ $download->type }}</span></td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $download->ip_hash }}</td>
                                <td class="px-5 py-3 text-slate-500 max-w-xs truncate">{{ $download->user_agent }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $download->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No downloads.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($downloads->hasPages())
                <div class="px-5 py-4 border-t border-slate-200">
                    {{ $downloads->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>