<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::withCount('conversions')->with(['plan'])->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['conversions' => fn ($q) => $q->latest()->limit(10), 'subscriptions' => fn ($q) => $q->with('plan')->latest(), 'plan']);

        return view('admin.users.show', compact('user'));
    }

    public function update(User $user)
    {
        $validated = request()->validate([
            'role' => ['nullable', 'in:user,admin'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $user->update(array_filter($validated));

        return back()->with('flash', 'User updated.');
    }

    public function impersonate(User $user)
    {
        session()->put('impersonate', $user->id);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating()
    {
        session()->forget('impersonate');

        return redirect()->route('admin.dashboard');
    }
}