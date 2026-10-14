<x-app-layout title="My Conversions">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-900">My Conversions</h1>
        <p class="mt-0.5 text-sm text-slate-500">All your image-to-SVG conversions.</p>
    </x-slot>

    <div class="space-y-4">
        <!-- Filters -->
        <div class="card">
            <form method="GET" action="{{ route('conversions.index') }}" class="card-body grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search by filename..." class="input w-full">
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
                <div>
                    <label for="from" class="sr-only">From</label>
                    <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="input w-full">
                </div>
                <div>
                    <label for="to" class="sr-only">To</label>
                    <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="input w-full">
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex justify-end gap-2">
                    <a href="{{ route('conversions.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="search" class="h-4 w-4" />
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="card">
            <div class="overflow-x-auto" x-data="{}">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
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
                                    <a href="{{ route('conversions.show', $conversion->uuid) }}" class="font-medium text-slate-800 hover:text-indigo-700">
                                        {{ $conversion->original_filename }}
                                    </a>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="badge badge-slate uppercase">{{ $conversion->conversion_type === 'b_w' ? 'B&W' : 'Color' }}</span>
                                </td>
                                <td class="px-5 py-3"><x-status-badge :status="$conversion->status" /></td>
                                <td class="px-5 py-3 text-slate-500">{{ round($conversion->file_size / 1024, 1) }} KB</td>
                                <td class="px-5 py-3 text-slate-500">{{ $conversion->created_at->format('M d, Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('conversions.show', $conversion->uuid) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View</a>

                                        <form method="POST" action="{{ route('conversions.destroy', $conversion->uuid) }}"
                                              @submit.prevent="if (confirm('Delete this conversion and all its files?')) $el.submit()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-800 text-xs font-semibold">
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <p class="text-slate-400">No conversions found.</p>
                                    <a href="{{ route('converter') }}" class="mt-2 inline-block text-sm font-semibold text-indigo-600">Start converting &rarr;</a>
                                </td>
                            </tr>
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