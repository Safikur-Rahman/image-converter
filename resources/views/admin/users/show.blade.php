<x-app-layout title="User Details">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">&larr; Back to users</a>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $user->name }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">{{ $user->email }}</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.users.impersonate', $user->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <x-icon name="user" class="h-4 w-4" />
                        Impersonate
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Account Details</h2></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Role</span><span class="badge @if($user->role === 'admin') badge-info @else badge-slate @endif">{{ ucfirst($user->role) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Plan</span><span class="font-medium text-slate-800">{{ $user->plan?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Member since</span><span class="font-medium text-slate-800">{{ $user->created_at->format('M d, Y') }}</span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Update Role &amp; Plan</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="role" class="label">Role</label>
                        <select name="role" id="role" class="input">
                            <option value="user" @selected($user->role === 'user')>User</option>
                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="plan_id" class="label">Plan</label>
                        <select name="plan_id" id="plan_id" class="input">
                            <option value="">No plan</option>
                            @foreach (\App\Models\Plan::where('is_active', true)->get() as $plan)
                                <option value="{{ $plan->id }}" @selected($user->plan_id === $plan->id)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <x-icon name="check" class="h-4 w-4" />
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Subscriptions</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Starts</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Renews</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($user->subscriptions as $subscription)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $subscription->plan?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span @class([
                                        'badge',
                                        'badge-success' => $subscription->status === 'active',
                                        'badge-warning' => $subscription->status === 'cancelled' || $subscription->status === 'past_due',
                                        'badge-slate' => in_array($subscription->status, ['inactive', 'expired', null]),
                                    ])>{{ ucfirst($subscription->status) }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-500">{{ $subscription->starts_at?->format('M d, Y') }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $subscription->renews_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No subscriptions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Recent Conversions</h2>
                <a href="{{ route('admin.conversions.index') }}?search={{ $user->email }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View all &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">File</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($user->conversions as $c)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $c->original_filename }}</td>
                                <td class="px-5 py-3"><x-status-badge :status="$c->status" /></td>
                                <td class="px-5 py-3 text-slate-500">{{ $c->created_at->format('M d, Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.conversions.show', $c->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No conversions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
