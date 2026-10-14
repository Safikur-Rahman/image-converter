<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        $conversionRows = DB::table('conversions')
            ->selectRaw("DATE(created_at) as day, COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $dailyConversions = $conversionRows->mapWithKeys(fn ($row) => [$row->day => (int) $row->total])->all();
        $monthly = DB::table('usage_records')
            ->where('period', now()->format('Y-m'))
            ->pluck('quantity', 'action')
            ->mapWithKeys(fn ($v, $k) => [$k => (int) $v])
            ->all();

        $storageBytes = (int) DB::table('conversion_files')->sum('size')
            + (int) DB::table('conversions')->sum('file_size');

        return view('admin.dashboard', [
            'stats' => [
                'users' => (int) DB::table('users')->count(),
                'conversions' => (int) DB::table('conversions')->count(),
                'completed' => (int) DB::table('conversions')->where('status', ConversionStatus::Completed)->count(),
                'failed' => (int) DB::table('conversions')->where('status', ConversionStatus::Failed)->count(),
                'processing' => (int) DB::table('conversions')->whereIn('status', ['pending', 'processing', 'background_removing', 'vectorizing', 'optimizing'])->count(),
                'background_removals' => (int) DB::table('background_removals')->count(),
                'storage_bytes' => $storageBytes,
                'downloads' => (int) DB::table('downloads')->count(),
                'failed_jobs' => (int) \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
            ],
            'dailyConversions' => $dailyConversions,
            'monthly' => $monthly,
        ]);
    }
}