<x-app-layout title="Subscriptions">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Subscriptions</h1>
            <p class="mt-0.5 text-sm text-slate-500">All subscription records.</p>
        </div>
    </x-slot>

    <div class="card" x-data="{}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Starts</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Renews</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ends</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($subscriptions as $subscription)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.users.show', $subscription->user_id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $subscription->user?->name ?? '—' }}</a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $subscription->plan?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'badge',
                                    'badge-success' => $subscription->status === 'active',
                                    'badge-warning' => in_array($subscription->status, ['cancelled', 'past_due']),
                                    'badge-slate' => !in_array($subscription->status, ['active', 'cancelled', 'past_due']),
                                ])>{{ ucfirst($subscription->status) }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $subscription->starts_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $subscription->renews_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $subscription->ends_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No subscriptions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscriptions->hasPages())
            <div class="px-5 py-4 border-t border-slate-200">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
