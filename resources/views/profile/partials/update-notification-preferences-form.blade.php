<form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('PATCH')

    @php
        $prefs = [
            'notify_conversion_completed' => ['Conversion completed', 'Email me when a conversion finishes successfully.'],
            'notify_conversion_failed' => ['Conversion failed', 'Email me when a conversion fails.'],
            'notify_background_removal_completed' => ['Background removal completed', 'Email me when background removal finishes.'],
            'notify_subscription_events' => ['Subscription events', 'Email me about plan changes, renewals and expirations.'],
        ];
    @endphp

    @foreach ($prefs as $key => [$label, $help])
        <label class="flex items-start justify-between gap-4 cursor-pointer">
            <div>
                <p class="text-sm font-medium text-slate-800">{{ $label }}</p>
                <p class="text-xs text-slate-400">{{ $help }}</p>
            </div>
            <span class="relative inline-flex shrink-0 mt-0.5">
                <input type="checkbox" name="{{ $key }}" value="1"
                       class="peer sr-only"
                       @checked((bool) $user->{$key} ?? true)>
                <span class="block h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-indigo-600"></span>
                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
            </span>
        </label>
    @endforeach

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">Save preferences</button>
    </div>
</form>