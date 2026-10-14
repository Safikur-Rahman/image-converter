<form method="post" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('patch')

    <div>
        <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="input w-full" required autofocus autocomplete="name">
        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input w-full" required autocomplete="username">
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2 text-sm text-slate-600">
                Your email is unverified.
                <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-indigo-600 underline hover:text-indigo-800 font-medium">Resend verification link</button>
                </form>
            </div>
            @if (session('status') === 'verification-link-sent')
                <p class="mt-1 text-xs font-medium text-emerald-600">Verification link sent.</p>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
        @if (session('status') === 'profile-updated')
            <span class="text-xs font-medium text-emerald-600 animate-in">Saved.</span>
        @endif
    </div>
</form>