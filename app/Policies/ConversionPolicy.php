<?php

namespace App\Policies;

use App\Models\Conversion;
use App\Models\User;

class ConversionPolicy
{
    public function view(User $user, Conversion $conversion): bool
    {
        return $user->isAdmin() || $user->id === $conversion->user_id;
    }

    public function create(User $user): bool
    {
        return app(\App\Services\Usage\UsageLimitService::class)->canConvert($user);
    }

    public function update(User $user, Conversion $conversion): bool
    {
        return $user->id === $conversion->user_id;
    }

    public function delete(User $user, Conversion $conversion): bool
    {
        return $user->id === $conversion->user_id;
    }

    public function download(User $user, Conversion $conversion): bool
    {
        return $user->id === $conversion->user_id || $user->isAdmin();
    }
}