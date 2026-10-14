<x-app-layout title="Conversion Details">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('conversions.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">&larr; Back to conversions</a>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $conversion->original_filename }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">{{ $conversion->created_at->format('M d, Y H:i') }} &middot; <x-status-badge :status="$conversion->status" /></p>
            </div>
            <div class="flex items-center gap-2">
                @if ($conversion->status === \App\Enums\ConversionStatus::Failed)
                    <form method="POST" action="{{ route('conversions.retry', $conversion->uuid) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <x-icon name="refresh" class="h-4 w-4" />
                            Retry
                        </button>
                    </form>
                @endif
                @if ($conversion->svgFile())
                    <a href="{{ route('downloads.svg', $conversion->uuid) }}" class="btn btn-primary btn-sm">
                        <x-icon name="download" class="h-4 w-4" />
                        Download SVG
                    </a>
                @endif
                <form method="POST" action="{{ route('conversions.destroy', $conversion->uuid) }}"
                      @submit.prevent="if (confirm('Delete this conversion and all its files?')) $el.submit()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" aria-label="Delete conversion">
                        <x-icon name="trash" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    @php
        $isFailed = $conversion->status === \App\Enums\ConversionStatus::Failed;
        $svgFile = $conversion->svgFile();
        $originalFile = $conversion->originalFile();
        $bgFile = $conversion->backgroundRemovedFile();
        $svgConversion = $conversion->svgConversion;

        $bgData = $bgFile ? \Illuminate\Support\Facades\Storage::disk($bgFile->disk ?? 'local')->get($bgFile->path) : null;
        $bgSrc = $bgData ? base64_encode($bgData) : null;
    @endphp

    <div class="card mb-6" x-data="conversionStatus({
        uuid: @json($conversion->uuid),
        started: @json(request()->query('started') === '1'),
        terminal: @json($conversion->status->isTerminal()),
    })">
        <div x-show="!terminal" x-cloak class="card-body">
            <h2 class="text-sm font-semibold text-slate-900">Conversion status</h2>
            <p class="mt-1 text-sm text-slate-500" x-text="statusLabel"></p>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ([
                    ['step' => 1, 'label' => 'Background', 'icon' => 'sparkles'],
                    ['step' => 2, 'label' => 'Tracing', 'icon' => 'converter'],
                    ['step' => 3, 'label' => 'Optimizing', 'icon' => 'settings'],
                    ['step' => 4, 'label' => 'Finished', 'icon' => 'check'],
                ] as $step)
                    <div class="rounded-lg border border-slate-200 p-3 text-center" :class="currentStep >= {{ $step['step'] }} ? 'border-indigo-300 bg-indigo-50 [&_svg]:text-indigo-600' : '[&_svg]:text-slate-300'">
                        <x-icon name="{{ $step['icon'] }}" class="mx-auto h-5 w-5" />
                        <p class="mt-1 text-xs font-medium" :class="currentStep >= {{ $step['step'] }} ? 'text-indigo-700' : 'text-slate-400'">{{ $step['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <p x-show="statusError" x-cloak class="mt-4 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700" x-text="statusError"></p>
        </div>

        @if ($isFailed)
            <div class="card-body">
                <div class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3">
                    <h2 class="text-sm font-semibold text-rose-800">This conversion failed</h2>
                    <p class="mt-1 text-sm text-rose-700">{{ $conversion->error_message ?? 'An unexpected error occurred during processing.' }}</p>
                </div>
            </div>
        @endif
    </div>

    <div @class(['grid grid-cols-1 gap-6 lg:grid-cols-3']) @if ($svgFile) x-data="svgPreview()" @endif>
        <div class="lg:col-span-2">
            <div class="card overflow-hidden" x-data="{ tab: 'preview' }">
                <div class="flex items-center gap-1 border-b border-slate-200 px-3 pt-3 overflow-x-auto">
                    <button type="button" @click="tab = 'preview'" class="tab-btn" :class="tab === 'preview' ? 'tab-btn-active' : 'tab-btn-inactive'">SVG Preview</button>
                    <button type="button" @click="tab = 'original'" class="tab-btn" :class="tab === 'original' ? 'tab-btn-active' : 'tab-btn-inactive'">Original</button>
                    @if ($bgSrc)
                        <button type="button" @click="tab = 'bg'" class="tab-btn" :class="tab === 'bg' ? 'tab-btn-active' : 'tab-btn-inactive'">No Background</button>
                    @endif
                    <button type="button" @click="tab = 'source'" class="tab-btn" :class="tab === 'source' ? 'tab-btn-active' : 'tab-btn-inactive'">SVG Source</button>
                </div>

                <div x-show="tab === 'preview'" class="svg-viewport" x-ref="viewport" @mousedown="dragStart($event)">
                    @if ($svgFile)
                        <img x-ref="svg" src="{{ route('downloads.preview-svg', $conversion->uuid) }}"
                             alt="SVG preview" draggable="false" decoding="async" class="absolute max-w-none select-none"
                             :style="{ transform, transformOrigin: '0 0' }" @load="fit()">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-sm text-slate-400">
                            @if ($conversion->status->isTerminal() && $conversion->status->value !== 'completed')
                                No SVG output for this conversion.
                            @else
                                Preview will appear once the vector is ready.
                            @endif
                        </div>
                    @endif
                </div>

                <template x-if="tab === 'original'">
                    <div class="svg-viewport checkerboard">
                        @if ($originalFile)
                            <img src="{{ route('downloads.preview-original', $conversion->uuid) }}" loading="lazy" decoding="async" class="absolute max-h-full max-w-full object-contain" alt="Original image">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-sm text-slate-400">Original image not available.</div>
                        @endif
                    </div>
                </template>

                @if ($bgSrc)
                    <template x-if="tab === 'bg'">
                        <div class="svg-viewport checkerboard">
                            <img :src="'data:{{ $bgFile->mime_type ?? 'image/png' }};base64,' + @json($bgSrc)" loading="lazy" decoding="async" class="absolute max-h-full max-w-full object-contain" alt="Background removed image">
                        </div>
                    </template>
                @endif

                <template x-if="tab === 'source'">
                    <div x-cloak class="max-h-[480px] overflow-auto bg-slate-900 p-4">
                        <pre class="text-xs leading-relaxed text-slate-100 whitespace-pre-wrap break-all">{{ $svgFile ? \Illuminate\Support\Facades\Storage::disk($svgFile->disk ?? 'local')->get($svgFile->path) : 'No SVG source available.' }}</pre>
                    </div>
                </template>

                <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-2.5">
                    <p class="hidden sm:block text-xs text-slate-400">Drag to pan &middot; scroll to zoom</p>
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn btn-secondary btn-sm" @mousedown.stop @click="fit()">Fit</button>
                        <button type="button" class="btn btn-secondary btn-sm" @mousedown.stop @click="zoomOut()"><x-icon name="zoom-out" class="h-3.5 w-3.5" /></button>
                        <button type="button" class="btn btn-secondary btn-sm" @mousedown.stop @click="zoomIn()"><x-icon name="zoom-in" class="h-3.5 w-3.5" /></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Conversion details</h2></div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Original format</span><span class="font-medium uppercase text-slate-800">{{ $conversion->original_format }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Dimensions</span><span class="font-medium text-slate-800">{{ $conversion->width }} &times; {{ $conversion->height }}px</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Conversion type</span><span class="font-medium uppercase text-slate-800">{{ $conversion->conversion_type === 'b_w' ? 'B&W' : 'Color' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Quality</span><span class="font-medium capitalize text-slate-800">{{ $conversion->quality }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Optimization</span><span class="font-medium capitalize text-slate-800">{{ $conversion->path_optimization }}</span></div>
                    @if ($conversion->remove_background)
                        <div class="flex justify-between"><span class="text-slate-500">Background removal</span><span class="badge badge-info">Yes</span></div>
                    @endif
                    @if ($svgConversion)
                        <div class="mt-2 border-t border-slate-100 pt-3">
                            <div class="flex justify-between"><span class="text-slate-500">SVG paths</span><span class="font-medium text-slate-800">{{ number_format($svgConversion->path_count) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">SVG size</span><span class="font-medium text-slate-800">{{ number_format($svgConversion->optimized_svg_size) }} bytes</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Tracing engine</span><span class="font-medium text-slate-800">{{ $svgConversion->tracing_engine }}</span></div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Downloads</h2></div>
                <div class="card-body space-y-2">
                    <a href="{{ route('downloads.svg', $conversion->uuid) }}" class="btn btn-primary w-full justify-center {{ $svgFile ? '' : 'pointer-events-none opacity-50' }}">
                        <x-icon name="download" class="h-4 w-4" /> SVG file
                    </a>
                    @if ($bgFile)
                        <a href="{{ route('downloads.background-removed', $conversion->uuid) }}" class="btn btn-secondary w-full justify-center">
                            <x-icon name="download" class="h-4 w-4" /> Background-removed image
                        </a>
                    @endif
                    @if ($originalFile)
                        <a href="{{ route('downloads.original', $conversion->uuid) }}" class="btn btn-secondary w-full justify-center">
                            <x-icon name="download" class="h-4 w-4" /> Original image
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>