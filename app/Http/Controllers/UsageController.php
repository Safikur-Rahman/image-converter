<?php

namespace App\Http\Controllers;

use App\Services\Usage\UsageLimitService;
use App\Services\Usage\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsageController extends Controller
{
    public function __invoke(Request $request, UsageTrackingService $tracking, UsageLimitService $limits): View
    {
        $user = $request->user();

        return view('usage', [
            'monthly' => $tracking->monthlyUsage($user, 6),
            'currentPeriod' => now()->format('Y-m'),
            'totalThisMonth' => $tracking->totalInMonth($user),
            'conversionsThisMonth' => $tracking->countForAction($user, 'svg_conversion'),
            'backgroundRemovalsThisMonth' => $tracking->countForAction($user, 'background_removal'),
            'downloadsThisMonth' => $tracking->countForAction($user, 'download'),
            'storageBytes' => $tracking->storageUsageBytes($user),
            'limit' => $user->effectivePlan()->monthly_conversion_limit,
            'remaining' => $limits->remainingConversions($user),
        ]);
    }
}