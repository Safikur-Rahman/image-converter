<x-app-layout title="Users">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Users</h1>
            <p class="mt-0.5 text-sm text-slate-500">All registered users.</p>
        </div>
    </x-slot>

    <div class="card" x-data="{}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Conversions</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Joined</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="font-medium text-slate-800 hover:text-indigo-700">{{ $user->name }}</a>
                                        <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'badge',
                                    'badge-info' => $user->role === 'admin',
                                    'badge-slate' => $user->role !== 'admin',
                                ])>{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $user->plan?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ number_format($user->conversions_count) }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
