<x-app-layout title="Conversion Details">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('admin.conversions.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">&larr; Back to conversions</a>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $conversion->original_filename }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">{{ $conversion->uuid }} &middot; {{ $conversion->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($conversion->status === \App\Enums\ConversionStatus::Failed)
                    <form method="POST" action="{{ route('admin.conversions.retry', $conversion->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <x-icon name="refresh" class="h-4 w-4" />
                            Retry
                        </button>
                    </form>
                @endif
                @if (!$conversion->status->isTerminal())
                    <form method="POST" action="{{ route('admin.conversions.cancel', $conversion->id) }}"
                          x-data @submit.prevent="if (confirm('Cancel this conversion?')) $el.submit()">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">
                            <x-icon name="x" class="h-4 w-4" />
                            Cancel
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Conversion Details</h2></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Status</span><x-status-badge :status="$conversion->status" /></div>
                <div class="flex justify-between"><span class="text-slate-500">User</span><span class="font-medium text-slate-800">{{ $conversion->user?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">UUID</span><span class="font-mono text-xs text-slate-600">{{ $conversion->uuid }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Original format</span><span class="font-medium uppercase text-slate-800">{{ $conversion->original_format }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Dimensions</span><span class="font-medium text-slate-800">{{ $conversion->width }} &times; {{ $conversion->height }}px</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Conversion type</span><span class="font-medium uppercase text-slate-800">{{ $conversion->conversion_type === 'b_w' ? 'B&W' : 'Color' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">File size</span><span class="font-medium text-slate-800">{{ round($conversion->file_size / 1024, 1) }} KB</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Quality</span><span class="font-medium capitalize text-slate-800">{{ $conversion->quality }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Optimization</span><span class="font-medium capitalize text-slate-800">{{ $conversion->path_optimization }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Background removal</span><span class="badge {{ $conversion->remove_background ? 'badge-info' : 'badge-slate' }}">{{ $conversion->remove_background ? 'Yes' : 'No' }}</span></div>
                @if ($conversion->svgConversion)
                    <div class="flex justify-between"><span class="text-slate-500">Tracing engine</span><span class="font-medium text-slate-800">{{ $conversion->svgConversion->tracing_engine }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">SVG paths</span><span class="font-medium text-slate-800">{{ number_format($conversion->svgConversion->path_count) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Optimized SVG size</span><span class="font-medium text-slate-800">{{ number_format($conversion->svgConversion->optimized_svg_size) }} bytes</span></div>
                @endif
            </div>
        </div>

        @if ($conversion->backgroundRemoval)
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Background Removal</h2></div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Provider</span><span class="font-medium text-slate-800">{{ $conversion->backgroundRemoval->provider }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-medium text-slate-800">{{ $conversion->backgroundRemoval->status }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Input path</span><span class="font-mono text-xs text-slate-600 break-all">{{ $conversion->backgroundRemoval->input_path }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Output path</span><span class="font-mono text-xs text-slate-600 break-all">{{ $conversion->backgroundRemoval->output_path }}</span></div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Files</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Filename</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Size</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">MIME</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($conversion->files as $file)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3"><span class="badge badge-slate">{{ $file->type }}</span></td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $file->filename }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ number_format($file->size / 1024, 1) }} KB</td>
                                <td class="px-5 py-3 text-slate-500">{{ $file->mime_type }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No files for this conversion.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($conversion->downloads->count())
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Recent Downloads</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">IP Hash</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User Agent</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Downloaded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($conversion->downloads as $download)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3"><span class="badge badge-slate">{{ $download->type }}</span></td>
                                    <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $download->ip_hash }}</td>
                                    <td class="px-5 py-3 text-slate-500 max-w-xs truncate">{{ $download->user_agent }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $download->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>