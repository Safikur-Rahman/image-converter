<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('price')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $val = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'unique:plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
            'monthly_conversion_limit' => ['required', 'integer', 'min:0'],
            'max_file_size_kb' => ['required', 'integer', 'min:1'],
            'max_dimensions' => ['required', 'integer', 'min:1'],
            'background_removal_enabled' => ['sometimes', 'boolean'],
            'api_enabled' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        Plan::create($val + ['background_removal_enabled' => $request->boolean('background_removal_enabled'), 'api_enabled' => $request->boolean('api_enabled'), 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('flash', 'Plan created.');
    }

    public function update(Plan $plan, Request $request)
    {
        $val = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
            'monthly_conversion_limit' => ['required', 'integer', 'min:0'],
            'max_file_size_kb' => ['required', 'integer', 'min:1'],
            'max_dimensions' => ['required', 'integer', 'min:1'],
            'background_removal_enabled' => ['sometimes', 'boolean'],
            'api_enabled' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $plan->update($val + ['background_removal_enabled' => $request->boolean('background_removal_enabled'), 'api_enabled' => $request->boolean('api_enabled'), 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('flash', 'Plan updated.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->slug === 'free') {
            return back()->with('flash', 'The free plan cannot be deleted.');
        }

        $plan->update(['is_active' => false]);

        return back()->with('flash', 'Plan disabled.');
    }
}