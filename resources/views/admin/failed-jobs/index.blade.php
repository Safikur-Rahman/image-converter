<x-app-layout title="Failed Jobs">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Failed Jobs</h1>
                <p class="mt-0.5 text-sm text-slate-500">Failed queue jobs and their exceptions.</p>
            </div>
            @if (count($jobs))
                <form method="POST" action="{{ route('admin.failed-jobs.flush') }}"
                      x-data @submit.prevent="if (confirm('Delete ALL failed jobs? This cannot be undone.')) $el.submit()">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">
                        <x-icon name="trash" class="h-4 w-4" />
                        Flush All
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4" x-data="{}">
        @forelse ($jobs as $job)
            <div class="card">
                <div class="card-header flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ $job->display_name ?? $job->job }}</h2>
                        <p class="mt-0.5 text-xs text-slate-400">Queue: {{ $job->queue }} &middot; Failed {{ $job->failed_at->format('M d, Y H:i:s') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.failed-jobs.retry', $job->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <x-icon name="refresh" class="h-4 w-4" />
                                Retry
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.failed-jobs.destroy', $job->id) }}"
                              @submit.prevent="if (confirm('Delete this failed job?')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <x-icon name="trash" class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-sm font-medium text-rose-700">{{ \Illuminate\Support\Str::limit($job->exception, 200) }}</p>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-indigo-600 hover:text-indigo-800">View full exception</summary>
                        <pre class="mt-2 max-h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-slate-100 whitespace-pre-wrap break-all">{{ $job->exception }}</pre>
                    </details>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body py-12 text-center">
                    <x-icon name="check" class="mx-auto h-8 w-8 text-emerald-500" />
                    <p class="mt-3 text-sm font-medium text-slate-700">No failed jobs.</p>
                    <p class="mt-1 text-xs text-slate-400">The queue is all clear.</p>
                </div>
            </div>
        @endforelse
    </div>
</x-app-layout>