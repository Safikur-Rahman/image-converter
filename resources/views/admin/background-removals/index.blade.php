<x-app-layout title="Background Removals">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Background Removals</h1>
            <p class="mt-0.5 text-sm text-slate-500">All AI background removal runs.</p>
        </div>
    </x-slot>

    <div class="card" x-data="{}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Conversion</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Provider</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Input</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Output</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($removals as $removal)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                @if ($removal->conversion)
                                    <a href="{{ route('admin.conversions.show', $removal->conversion_id) }}" class="font-medium text-slate-800 hover:text-indigo-700">
                                        {{ $removal->conversion->original_filename }}
                                    </a>
                                    <p class="text-xs text-slate-400">{{ $removal->conversion->uuid }}</p>
                                    <p class="text-xs text-slate-400">{{ $removal->conversion->user?->name ?? '—' }}</p>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $removal->provider }}</td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'badge',
                                    'badge-success' => $removal->status === 'completed',
                                    'badge-danger' => $removal->status === 'failed',
                                    'badge-warning' => $removal->status === 'processing',
                                    'badge-slate' => !in_array($removal->status, ['completed', 'failed', 'processing']),
                                ])>{{ ucfirst($removal->status) }}</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600 break-all">{{ $removal->input_path }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600 break-all">{{ $removal->output_path ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $removal->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No background removals found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($removals->hasPages())
            <div class="px-5 py-4 border-t border-slate-200">
                {{ $removals->links() }}
            </div>
        @endif
    </div>
</x-app-layout>