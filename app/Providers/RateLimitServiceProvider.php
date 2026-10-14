<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // API rate limiter that keys on userId for authenticated calls.
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if ($user) {
                $quota = $this->quotaFor($user);

                return Limit::perMinute($quota)->by('api|'.$user->id);
            }

            return Limit::perMinute(20)->by('api-guest|'.$request->ip());
        });

        // Per-user conversion submissions: 20 per minute, 200 per day.
        RateLimiter::for('conversions', function (Request $request) {
            $user = $request->user();

            $key = $user ? 'conversions|'.$user->id : 'conversions-guest|'.$request->ip();

            return [
                Limit::perMinute(20)->by($key),
                Limit::perDay(200)->by($key.'-day'),
            ];
        });

        // Heavy, credential-guessing-proof login throttling is handled by
        // Fortify defaults when used; here we keep a standalone global guard.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by('login|'.$request->ip());
        });
    }

    private function quotaFor(\App\Models\User $user): int
    {
        if ($user->isAdmin()) {
            return 500;
        }

        $plan = $user->effectivePlan();

        return (int) $plan->api_enabled ? 120 : 30;
    }
}