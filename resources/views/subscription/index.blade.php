<x-app-layout title="Subscription">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-900">Subscription</h1>
        <p class="mt-0.5 text-sm text-slate-500">Manage your plan and billing cycle.</p>
    </x-slot>

    <div class="space-y-6">
        @if ($currentPlan && $currentPlan->slug !== 'free')
            <div class="card border-indigo-200">
                <div class="card-body flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Current plan</p>
                        <p class="text-lg font-bold text-slate-900">{{ $currentPlan->name }}</p>
                        @if ($subscription)
                            <p class="text-xs text-slate-400">
                                @if ($subscription->status === 'active')
                                    Renews {{ $subscription->renews_at?->format('M d, Y') ?? 'soon' }}
                                @else
                                    Canceled {{ $subscription->cancelled_at?->format('M d, Y') }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('subscription.cancel') }}"
                          @submit.prevent="if (confirm('Downgrade to the free plan?')) $el.submit()">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">Cancel subscription</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @forelse ($plans as $plan)
                <div class="card p-6 {{ $currentPlan?->id === $plan->id ? 'border-indigo-300 ring-2 ring-indigo-100' : '' }}">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                        @if ($currentPlan?->id === $plan->id)
                            <span class="badge badge-info">Current</span>
                        @endif
                    </div>
                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        ${{ number_format($plan->price, 2) }}<span class="text-sm font-normal text-slate-400">/month</span>
                    </p>

                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        <li class="flex items-center gap-2">
                            <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                            {{ $plan->monthly_conversion_limit === -1 ? 'Unlimited conversions' : number_format($plan->monthly_conversion_limit).' conversions / month' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                            {{ number_format($plan->max_file_size_kb) }} KB max file size
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                            {{ $plan->background_removal_enabled ? 'Background removal included' : 'No background removal' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                            {{ $plan->api_enabled ? 'API access included' : 'No API access' }}
                        </li>
                    </ul>

                    @if ($currentPlan?->id === $plan->id)
                        <p class="mt-6 text-center text-sm font-medium text-slate-400">Your current plan</p>
                    @else
                        <form method="POST" action="{{ route('subscription.subscribe', $plan) }}" class="mt-6">
                            @csrf
                            <button type="submit" class="btn {{ $plan->price > 0 ? 'btn-primary' : 'btn-secondary' }} w-full">
                                {{ $plan->price > 0 ? 'Subscribe' : 'Switch to free' }}
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="col-span-full text-center text-slate-400">No plans available.</p>
            @endforelse
        </div>

        @if ($history->isNotEmpty())
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-semibold text-slate-900">Subscription history</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Start</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">End</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($history as $sub)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $sub->plan->name }}</td>
                                    <td class="px-5 py-3">
                                        <span class="badge {{ $sub->status === 'active' ? 'badge-success' : 'badge-slate' }}">{{ ucfirst($sub->status) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500">{{ $sub->starts_at?->format('M d, Y') }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $sub->ends_at?->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>