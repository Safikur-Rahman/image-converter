<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('subscription.index', [
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'currentPlan' => $user->effectivePlan(),
            'subscription' => $user->activeSubscription(),
            'history' => $user->subscriptions()->with('plan')->latest()->limit(10)->get(),
        ]);
    }

    public function subscribe(Request $request, Plan $plan): \Illuminate\Http\RedirectResponse
    {
        if (!$plan->is_active) {
            return back()->with('flash', 'This plan is not available.');
        }

        $user = $request->user();

        // Cancel any existing active subscription history.
        $user->subscriptions()->where('status', 'active')->update([
            'status' => 'canceled',
            'cancelled_at' => now(),
        ]);

        Subscription::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => $plan->billing_interval === 'monthly' ? now()->addMonth() : now()->addYear(),
            'renews_at' => $plan->billing_interval === 'monthly' ? now()->addMonth() : now()->addYear(),
        ]);

        $user->update(['plan_id' => $plan->id]);

        return back()->with('flash', 'You are now subscribed to the '.$plan->name.' plan.');
    }

    public function cancel(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        $active = $user->activeSubscription();
        if ($active) {
            $active->update(['status' => 'canceled', 'cancelled_at' => now()]);
        }

        $free = Plan::where('slug', 'free')->first();
        $user->update(['plan_id' => $free?->id ?? null]);

        return back()->with('flash', 'Your subscription has been cancelled.');
    }
}