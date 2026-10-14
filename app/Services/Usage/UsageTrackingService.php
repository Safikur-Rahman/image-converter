<?php

namespace App\Services\Usage;

use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Support\Str;

class UsageTrackingService
{
    public function record(User $user, string $action, int $quantity = 1, array $metadata = [], ?string $period = null): UsageRecord
    {
        $period ??= now()->format('Y-m');

        return UsageRecord::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'action' => $action,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'period' => $period,
        ]);
    }

    public function countForAction(User $user, string $action, ?string $period = null): int
    {
        $period ??= now()->format('Y-m');

        return (int) UsageRecord::where('user_id', $user->id)
            ->where('period', $period)
            ->where('action', $action)
            ->sum('quantity');
    }

    public function totalInMonth(User $user, ?string $period = null): int
    {
        $period ??= now()->format('Y-m');

        return (int) UsageRecord::where('user_id', $user->id)
            ->where('period', $period)
            ->sum('quantity');
    }

    public function storageUsageBytes(User $user): int
    {
        return (int) $user->conversions()->sum('file_size')
            + (int) \App\Models\ConversionFile::query()
                ->whereIn('conversion_id', $user->conversions()->select('id'))
                ->sum('size');
    }

    /**
     * @return array<int, array{month: string, conversions: int}>
     */
    public function monthlyUsage(User $user, int $months = 6): array
    {
        $totals = $this->conversionCountByPeriod($user, $months);

        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $period = now()->subMonths($i)->format('Y-m');
            $out[] = [
                'month' => $period,
                'conversions' => $totals[$period] ?? 0,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, int> period => total svg conversions
     */
    private function conversionCountByPeriod(User $user, int $months): array
    {
        $since = now()->subMonths($months - 1)->startOfMonth();

        return UsageRecord::where('user_id', $user->id)
            ->where('period', '>=', $since->format('Y-m'))
            ->where('action', 'svg_conversion')
            ->selectRaw('period, SUM(quantity) as total')
            ->groupBy('period')
            ->pluck('total', 'period')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}