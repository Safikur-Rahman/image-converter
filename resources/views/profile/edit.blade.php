<x-app-layout title="Profile">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-900">Profile &amp; Settings</h1>
        <p class="mt-0.5 text-sm text-slate-500">Manage your account information and notification preferences.</p>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Profile information</h2></div>
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Notification preferences</h2></div>
            <div class="card-body">
                @include('profile.partials.update-notification-preferences-form')
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Password</h2></div>
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card border-rose-200">
            <div class="card-header"><h2 class="text-sm font-semibold text-rose-700">Danger zone</h2></div>
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>