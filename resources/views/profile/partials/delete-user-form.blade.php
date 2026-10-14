<div class="space-y-4">
    <p class="text-sm text-slate-500">Once your account is deleted, all of its resources and data will be permanently deleted. Download any data you wish to keep first.</p>

    <div x-data="{ show: false }">
        <button type="button" @click="show = true" class="btn btn-danger btn-sm">Delete account</button>

        <div x-show="show" x-cloak x-transition class="fixed inset-0 z-40 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/40" @click="show = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl p-6">
                <h3 class="text-base font-semibold text-slate-900">Delete your account?</h3>
                <p class="mt-2 text-sm text-slate-500">Enter your password to confirm this action. This cannot be undone.</p>

                <form method="post" action="{{ route('profile.destroy') }}" class="mt-4">
                    @csrf
                    @method('delete')
                    <label for="delete-password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Password</label>
                    <input id="delete-password" name="password" type="password" class="input w-full" placeholder="Password" required>
                    @error('userDeletion.password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="show = false" class="btn btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Delete account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>