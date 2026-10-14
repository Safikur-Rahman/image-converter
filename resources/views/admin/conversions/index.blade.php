<x-app-layout title="Conversions">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Conversions</h1>
                <p class="mt-0.5 text-sm text-slate-500">All conversions across the platform.</p>
            </div>
            <form method="POST" action="{{ route('admin.conversions.retry-all') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="refresh" class="h-4 w-4" />
                    Retry All Failed
                </button>
            </form>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="card">
            <form method="GET" action="{{ route('admin.conversions.index') }}" class="card-body grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search by filename or UUID..." class="input w-full">
                </div>
                <div>
                    <label for="status" class="sr-only">Status</label>
                    <select name="status" id="status" class="input w-full">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <a href="{{ route('admin.conversions.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="search" class="h-4 w-4" />
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="card" x-data="{}">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">File</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Size</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($conversions as $conversion)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.users.show', $conversion->user_id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $conversion->user?->name ?? '—' }}</a>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800">{{ $conversion->original_filename }}</p>
                                    <p class="text-xs text-slate-400">{{ $conversion->uuid }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="badge badge-slate uppercase">{{ $conversion->conversion_type === 'b_w' ? 'B&W' : 'Color' }}</span>
                                </td>
                                <td class="px-5 py-3"><x-status-badge :status="$conversion->status" /></td>
                                <td class="px-5 py-3 text-slate-500">{{ round($conversion->file_size / 1024, 1) }} KB</td>
                                <td class="px-5 py-3 text-slate-500">{{ $conversion->created_at->format('M d, Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.conversions.show', $conversion->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No conversions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($conversions->hasPages())
                <div class="px-5 py-4 border-t border-slate-200">
                    {{ $conversions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
