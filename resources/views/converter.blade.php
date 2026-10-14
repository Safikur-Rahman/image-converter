<x-app-layout title="Converter">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-900">Image Converter</h1>
        <p class="mt-0.5 text-sm text-slate-500">Convert raster images into clean, scaleable SVG vector files.</p>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="converterComponent({ maxBytes: {{ $maxFileSizeBytes }}, maxDims: {{ $maxDimensions }} })">
        <!-- Left column: upload + options -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-900">1. Upload your image</h2>
                    <p class="text-xs text-slate-400">PNG, JPG or WEBP &middot; up to {{ $maxFileSizeMb }} MB &middot; max {{ $maxDimensions }}px</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('converter.store') }}" enctype="multipart/form-data" id="converter-form"
                          @submit.prevent="submitUpload">
                        @csrf
                        <input type="hidden" name="remove_background" :value="removeBackground ? '1' : '0'">
                        <input type="hidden" name="conversion_type" :value="conversionType">
                        <input type="hidden" name="quality" :value="quality">
                        <input type="hidden" name="path_optimization" :value="pathOptimization">

                        <!-- Dropzone -->
                        <div class="dropzone"
                             :class="{ 'dropzone-active': dragActive }"
                             @dragover.prevent="dragActive = true"
                             @dragleave.prevent="dragActive = false"
                             @drop.prevent="handleDrop($event)">
                            <input type="file" name="image" id="image-input" x-ref="fileInput" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                                   class="hidden" @change="handleFile($event.target.files[0])">

                            <template x-if="!fileName">
                                <button type="button" class="flex flex-col items-center gap-2 text-center" @click="$refs.fileInput.click()">
                                    <span class="rounded-full bg-indigo-100 p-4 text-indigo-600">
                                        <x-icon name="upload" class="h-8 w-8" />
                                    </span>
                                    <span class="text-sm font-semibold text-slate-700">Drag &amp; drop an image here</span>
                                    <span class="text-xs text-slate-400">or click to browse files</span>
                                </button>
                            </template>

                            <template x-if="fileName">
                                <div class="flex w-full items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="rounded-lg bg-slate-100 p-2 text-slate-500 text-xs font-bold uppercase" x-text="fileExt"></span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-800" x-text="fileName"></p>
                                            <p class="text-xs text-slate-400" x-text="fileSizeText"></p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="resetFile">Change</button>
                                </div>
                            </template>
                        </div>

                        <p x-show="uploadError" x-cloak class="mt-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700" x-text="uploadError"></p>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-semibold text-slate-900">2. Choose options</h2>
                </div>
                <div class="card-body space-y-5">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Conversion type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="option-card">
                                <input type="radio" name="conversion_type" value="color" x-model="conversionType" class="peer sr-only">
                                <span class="option-body">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-300"><span x-show="conversionType === 'color'" class="h-2.5 w-2.5 rounded-full bg-indigo-600"></span></span>
                                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <span class="flex gap-0.5">
                                            <span class="inline-block h-4 w-2 rounded-sm" style="background:#ef4444"></span>
                                            <span class="inline-block h-4 w-2 rounded-sm" style="background:#22c55e"></span>
                                            <span class="inline-block h-4 w-2 rounded-sm" style="background:#3b82f6"></span>
                                        </span>
                                        Color
                                    </span>
                                </span>
                            </label>
                            <label class="option-card">
                                <input type="radio" name="conversion_type" value="b_w" x-model="conversionType" class="peer sr-only">
                                <span class="option-body">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-300"><span x-show="conversionType === 'b_w'" class="h-2.5 w-2.5 rounded-full bg-indigo-600"></span></span>
                                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <span class="flex gap-0.5">
                                            <span class="inline-block h-4 w-2 rounded-sm bg-slate-300"></span>
                                            <span class="inline-block h-4 w-2 rounded-sm bg-slate-500"></span>
                                            <span class="inline-block h-4 w-2 rounded-sm bg-slate-800"></span>
                                        </span>
                                        Black &amp; White
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Quality</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach (['low', 'medium', 'high'] as $q)
                                <label class="option-card">
                                    <input type="radio" name="quality" value="{{ $q }}" x-model="quality" class="peer sr-only">
                                    <span class="option-body justify-center">
                                        <span class="text-sm font-semibold text-slate-800 capitalize">{{ $q }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Path optimization</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach (['simple', 'balanced', 'aggressive'] as $o)
                                <label class="option-card">
                                    <input type="radio" name="path_optimization" value="{{ $o }}" x-model="pathOptimization" class="peer sr-only">
                                    <span class="option-body justify-center">
                                        <span class="text-sm font-semibold text-slate-800 capitalize">{{ $o }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($backgroundRemovalAvailable && $backgroundRemovalEnabled)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="flex items-start justify-between gap-3 cursor-pointer">
                                <div>
                                    <p class="flex items-center gap-1.5 text-sm font-semibold text-slate-800">
                                        <x-icon name="sparkles" class="h-4 w-4 text-indigo-500" />
                                        Remove background first
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-400">Isolate the subject before tracing for a cleaner vector.</p>
                                </div>
                                <span class="relative inline-flex shrink-0">
                                    <input type="checkbox" x-model="removeBackground" class="sr-only">
                                    <span class="block h-6 w-11 rounded-full bg-slate-200 transition" :class="removeBackground ? 'bg-indigo-600' : ''"></span>
                                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                                          :class="removeBackground ? 'translate-x-5' : ''"></span>
                                </span>
                            </label>
                        </div>
                    @else
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs text-slate-400 flex items-center gap-2">
                            <x-icon name="sparkles" class="h-4 w-4" />
                            Background removal is not available on this deployment yet.
                        </div>
                    @endif

                    @error('limit')
                        <p class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Right column: limits + submit -->
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">3. Convert</h2></div>
                <div class="card-body space-y-4">
                    <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm flex items-center justify-between">
                        <span class="text-slate-500">Conversions remaining this month</span>
                        <span class="font-bold text-indigo-600">{{ $remaining }}</span>
                    </div>

                    <button type="submit" form="converter-form" class="btn btn-primary w-full" :disabled="!canUpload || uploading">
                        <span x-show="!uploading" class="inline-flex items-center gap-2">
                            <x-icon name="converter" class="h-4 w-4" />
                            Convert to SVG
                        </span>
                        <span x-show="uploading" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            Uploading&hellip;
                        </span>
                    </button>

                    <p x-show="uploadProgress > 0" x-cloak class="text-xs text-slate-400 text-center">
                        <span x-text="uploadProgress"></span>%
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body space-y-3 text-sm text-slate-500">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Format limits</h3>
                    <p class="flex items-center gap-2"><x-icon name="file" class="h-4 w-4 text-slate-400" /> Max file size: <strong class="text-slate-700">{{ $maxFileSizeMb }} MB</strong></p>
                    <p class="flex items-center gap-2"><x-icon name="settings" class="h-4 w-4 text-slate-400" /> Max dimensions: <strong class="text-slate-700">{{ $maxDimensions }}px</strong></p>
                    <p class="flex items-center gap-2"><x-icon name="shield" class="h-4 w-4 text-slate-400" /> Detect virus / corrupt files: <strong class="text-slate-700">on</strong></p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>