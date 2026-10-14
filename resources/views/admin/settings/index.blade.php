<x-app-layout title="Settings">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Settings</h1>
            <p class="mt-0.5 text-sm text-slate-500">App-wide configuration.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Application Settings</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.update') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="max_upload_size" class="label">Max upload size (KB)</label>
                        <input type="number" name="max_upload_size" id="max_upload_size" min="1"
                               value="{{ $settings['max_upload_size'] ?? config('imageconverter.max_upload_size_kb') }}" class="input">
                    </div>

                    <div>
                        <label for="allowed_formats" class="label">Allowed formats (comma separated)</label>
                        <input type="text" name="allowed_formats" id="allowed_formats"
                               value="{{ $settings['allowed_formats'] ?? implode(',', config('imageconverter.allowed_formats')) }}" class="input">
                    </div>

                    <div>
                        <label for="default_svg_quality" class="label">Default SVG quality</label>
                        <select name="default_svg_quality" id="default_svg_quality" class="input">
                            @foreach (['low', 'medium', 'high'] as $quality)
                                <option value="{{ $quality }}" @selected(($settings['default_svg_quality'] ?? config('imageconverter.default_svg_quality')) === $quality)>{{ ucfirst($quality) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="storage_retention_days" class="label">Storage retention (days)</label>
                        <input type="number" name="storage_retention_days" id="storage_retention_days" min="0"
                               value="{{ $settings['storage_retention_days'] ?? config('imageconverter.storage_retention_days') }}" class="input">
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-2">
                        <input type="checkbox" name="background_removal_available" id="background_removal_available" value="1"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               @checked((int) ($settings['background_removal_available'] ?? 0) === 1)>
                        <label for="background_removal_available" class="text-sm font-medium text-slate-700">Background removal available</label>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="maintenance_message" class="label">Maintenance message</label>
                        <textarea name="maintenance_message" id="maintenance_message" rows="3" class="input">{{ $settings['maintenance_message'] ?? '' }}</textarea>
                        <p class="mt-1 text-xs text-slate-400">Blank disables the maintenance notice.</p>
                    </div>

                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <x-icon name="check" class="h-4 w-4" />
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">System Info</h2></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Storage disk</span>
                    <span class="font-medium text-slate-800">{{ $storage['disk'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Storage root</span>
                    <span class="font-mono text-xs text-slate-600 break-all">{{ $storage['root'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Background removal provider</span>
                    @if ($providerConfigured)
                        <span class="badge badge-success">{{ $providerName }}</span>
                    @else
                        <span class="badge badge-slate">Not configured</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>