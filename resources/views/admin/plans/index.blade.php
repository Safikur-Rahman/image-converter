<x-app-layout title="Plans">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Plans</h1>
                <p class="mt-0.5 text-sm text-slate-500">Manage subscription plans.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" @click="newOpen = true">
                <x-icon name="sparkles" class="h-4 w-4" />
                New Plan
            </button>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ newOpen: false, editOpen: false, editPlan: null, editIndex: null }">
        <div class="card" x-show="newOpen" x-cloak>
            <div class="card-header flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">New Plan</h2>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="newOpen = false"><x-icon name="close" class="h-4 w-4" /></button>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.plans.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @csrf
                    <div>
                        <label for="name" class="label">Name</label>
                        <input type="text" name="name" id="name" class="input" required>
                    </div>
                    <div>
                        <label for="slug" class="label">Slug</label>
                        <input type="text" name="slug" id="slug" class="input" required>
                    </div>
                    <div>
                        <label for="price" class="label">Price</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" class="input" required>
                    </div>
                    <div>
                        <label for="billing_interval" class="label">Billing Interval</label>
                        <select name="billing_interval" id="billing_interval" class="input">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div>
                        <label for="monthly_conversion_limit" class="label">Monthly Conversion Limit</label>
                        <input type="number" name="monthly_conversion_limit" id="monthly_conversion_limit" min="0" class="input" required>
                    </div>
                    <div>
                        <label for="max_file_size_kb" class="label">Max File Size (KB)</label>
                        <input type="number" name="max_file_size_kb" id="max_file_size_kb" min="1" class="input" required>
                    </div>
                    <div>
                        <label for="max_dimensions" class="label">Max Dimensions</label>
                        <input type="number" name="max_dimensions" id="max_dimensions" min="1" class="input" required>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-2 flex items-end gap-6 pb-1">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="background_removal_enabled" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Background removal
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="api_enabled" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            API enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Active
                        </label>
                    </div>
                    <div class="flex items-end justify-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <x-icon name="check" class="h-4 w-4" />
                            Create Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" x-data="{}">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Price</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Limit</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Features</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subs</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($plans as $index => $plan)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800">{{ $plan->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $plan->slug }}</p>
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    ${{ number_format($plan->price, 2) }} <span class="text-xs text-slate-400">/ {{ $plan->billing_interval }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    <p>{{ number_format($plan->monthly_conversion_limit) }} conv</p>
                                    <p class="text-xs text-slate-400">{{ number_format($plan->max_file_size_kb) }} KB &middot; {{ $plan->max_dimensions }}px</p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($plan->background_removal_enabled)
                                            <span class="badge badge-info">BG removal</span>
                                        @endif
                                        @if ($plan->api_enabled)
                                            <span class="badge badge-violet" style="background:#eef2ff;color:#4338ca">API</span>
                                        @endif
                                        @if ($plan->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-slate">Inactive</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($plan->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-slate">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ number_format($plan->subscriptions_count) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold" @click="editPlan = @js($plan->toArray()); editIndex = @js($index); editOpen = true">Edit</button>
                                        @if ($plan->slug !== 'free')
                                            <form method="POST" action="{{ route('admin.plans.destroy', $plan->id) }}"
                                                  @submit.prevent="if (confirm('Disable this plan? Existing subscribers may be affected.')) $el.submit()">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">Disable</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No plans found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" x-show="editOpen" x-cloak @keydown.escape.window="editOpen = false">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl" x-show="editPlan">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900" x-text="editPlan.name"></h2>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="editOpen = false"><x-icon name="close" class="h-5 w-5" /></button>
                </div>
                <div class="p-5">
                    <form method="POST" :action="'/admin/plans/' + editPlan.id" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="label">Name</label>
                            <input type="text" name="name" x-model="editPlan.name" class="input" required>
                        </div>
                        <div>
                            <label class="label">Price</label>
                            <input type="number" name="price" step="0.01" min="0" x-model="editPlan.price" class="input" required>
                        </div>
                        <div>
                            <label class="label">Billing Interval</label>
                            <select name="billing_interval" x-model="editPlan.billing_interval" class="input">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Monthly Conversion Limit</label>
                            <input type="number" name="monthly_conversion_limit" min="0" x-model="editPlan.monthly_conversion_limit" class="input" required>
                        </div>
                        <div>
                            <label class="label">Max File Size (KB)</label>
                            <input type="number" name="max_file_size_kb" min="1" x-model="editPlan.max_file_size_kb" class="input" required>
                        </div>
                        <div>
                            <label class="label">Max Dimensions</label>
                            <input type="number" name="max_dimensions" min="1" x-model="editPlan.max_dimensions" class="input" required>
                        </div>
                        <div class="sm:col-span-2 flex items-center gap-6">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="background_removal_enabled" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" x-model="editPlan.background_removal_enabled">
                                Background removal
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="api_enabled" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" x-model="editPlan.api_enabled">
                                API enabled
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" x-model="editPlan.is_active">
                                Active
                            </label>
                        </div>
                        <div class="sm:col-span-2 flex justify-end gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" @click="editOpen = false">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <x-icon name="check" class="h-4 w-4" />
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
