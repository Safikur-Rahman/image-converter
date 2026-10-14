<?php

namespace App\Http\Controllers;

use App\Services\Usage\UsageLimitService;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $plans = \App\Models\Plan::where('is_active', true)->orderBy('price')->get();

        return view('landing', [
            'plans' => $plans,
        ]);
    }
}