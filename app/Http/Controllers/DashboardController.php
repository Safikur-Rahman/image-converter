<?php

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Services\Usage\UsageLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request, UsageLimitService $limits)
    {
        $user = $request->user();

        $stats = [
            'total' => $user->conversions()->count(),
            'completed' => $user->conversions()->where('status', ConversionStatus::Completed)->count(),
            'processing' => $user->conversions()
                ->whereIn('status', [
                    ConversionStatus::Pending,
                    ConversionStatus::Processing,
                    ConversionStatus::BackgroundRemoving,
                    ConversionStatus::Vectorizing,
                    ConversionStatus::Optimizing,
                ])->count(),
            'failed' => $user->conversions()->where('status', ConversionStatus::Failed)->count(),
            'storage_bytes' => (int) $user->conversions()->sum('file_size'),
        ];

        $monthly = DB::table('usage_records')
            ->where('user_id', $user->id)
            ->where('period', now()->format('Y-m'))
            ->pluck('quantity', 'action')
            ->mapWithKeys(fn ($v, $k) => [$k => (int) $v])
            ->all();

        $recent = $user->conversions()
            ->with('files')
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboard', [
            'stats' => $stats,
            'monthly' => $monthly,
            'recent' => $recent,
            'remaining' => $limits->remainingConversions($user),
            'storageLimitText' => $user->effectivePlan()->max_file_size_kb.' KB max per file',
        ]);
    }
}