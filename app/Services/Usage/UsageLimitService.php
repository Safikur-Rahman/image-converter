<?php

namespace App\Services\Usage;

use App\Models\User;
use App\Support\AppSettings;

/**
 * Single source of truth for plan limits. Controllers and jobs ask this
 * service instead of hard-coding any quota anywhere.
 */
class UsageLimitService
{
    public function __construct(
        protected AppSettings $settings,
        protected UsageTrackingService $tracking,
    ) {}

    public function canConvert(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $plan = $user->effectivePlan();
        $limit = (int) $plan->monthly_conversion_limit;

        if ($limit === 0) {
            return false;
        }

        if ($limit < 0) {
            return true; // unlimited
        }

        return $this->tracking->countForAction($user, 'svg_conversion') < $limit;
    }

    public function canRemoveBackground(User $user): bool
    {
        if (!$this->settings->backgroundRemovalAvailable()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return (bool) $user->effectivePlan()->background_removal_enabled;
    }

    public function canUseApi(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (bool) $user->effectivePlan()->api_enabled;
    }

    public function maxFileSizeBytes(User $user): int
    {
        $plan = $user->effectivePlan();
        $planBytes = (int) $plan->max_file_size_kb * 1024;

        $global = $this->settings->maxUploadSizeBytes();

        if ($planBytes <= 0) {
            return $global;
        }

        return min($planBytes, $global);
    }

    public function maxDimensions(User $user): int
    {
        $plan = $user->effectivePlan();

        return (int) $plan->max_dimensions ?: (int) config('imageconverter.max_dimensions', 8000);
    }

    public function remainingConversions(User $user): int
    {
        $plan = $user->effectivePlan();
        if ($user->isAdmin()) {
            return PHP_INT_MAX;
        }

        $limit = (int) $plan->monthly_conversion_limit;
        if ($limit <= 0) {
            return $limit === 0 ? 0 : PHP_INT_MAX;
        }

        return max(0, $limit - $this->tracking->countForAction($user, 'svg_conversion'));
    }

    public function conversionsLeftText(User $user): string
    {
        $remaining = $this->remainingConversions($user);

        if ($remaining === PHP_INT_MAX) {
            return 'Unlimited';
        }

        return (string) $remaining;
    }
}