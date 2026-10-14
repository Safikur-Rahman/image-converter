<x-app-layout title="System Logs">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">System Logs</h1>
            <p class="mt-0.5 text-sm text-slate-500">Application logs and errors.</p>
        </div>
    </x-slot>

    <div class="card" x-data="{}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Level</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Message</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Conversion</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <span @class([
                                    'badge',
                                    'badge-danger' => $log->level === 'error',
                                    'badge-warning' => $log->level === 'warning',
                                    'badge-info' => $log->level === 'info',
                                    'badge-slate' => !in_array($log->level, ['error', 'warning', 'info']),
                                ])>{{ ucfirst($log->level) }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $log->source }}</td>
                            <td class="px-5 py-3 text-slate-700 max-w-md">
                                <p class="truncate">{{ \Illuminate\Support\Str::limit($log->message, 140) }}</p>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $log->conversion_uuid ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $log->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No logs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-app-layout>