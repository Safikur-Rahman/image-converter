<form method="post" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    @method('put')

    <div>
        <label for="update_password_current_password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Current password</label>
        <input id="update_password_current_password" name="current_password" type="password" class="input w-full" autocomplete="current-password">
        @error('updatePassword.current_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="update_password_password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">New password</label>
        <input id="update_password_password" name="password" type="password" class="input w-full" autocomplete="new-password">
        @error('updatePassword.password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="update_password_password_confirmation" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Confirm password</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="input w-full" autocomplete="new-password">
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
        @if (session('status') === 'password-updated')
            <span class="text-xs font-medium text-emerald-600 animate-in">Saved.</span>
        @endif
    </div>
</form>