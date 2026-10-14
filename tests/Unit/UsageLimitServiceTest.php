<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\User;
use App\Services\Usage\UsageLimitService;
use App\Services\Usage\UsageTrackingService;
use App\Support\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): UsageLimitService
    {
        return new UsageLimitService(app(AppSettings::class), app(UsageTrackingService::class));
    }

    private function userWithLimit(int $limit): User
    {
        $plan = Plan::create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'price' => 0,
            'billing_interval' => 'monthly',
            'monthly_conversion_limit' => $limit,
            'max_file_size_kb' => 10240,
            'max_dimensions' => 8000,
            'background_removal_enabled' => true,
            'api_enabled' => true,
            'is_active' => true,
        ]);

        return User::factory()->create(['plan_id' => $plan->id]);
    }

    public function test_admin_can_always_convert(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'plan_id' => null]);

        $this->assertTrue($this->service()->canConvert($admin));
        $this->assertSame(PHP_INT_MAX, $this->service()->remainingConversions($admin));
    }

    public function test_zero_limit_blocks_conversion(): void
    {
        $user = $this->userWithLimit(0);

        $this->assertFalse($this->service()->canConvert($user));
        $this->assertSame(0, $this->service()->remainingConversions($user));
    }

    public function test_negative_limit_means_unlimited(): void
    {
        $user = $this->userWithLimit(-1);

        $this->assertTrue($this->service()->canConvert($user));
        $this->assertSame(PHP_INT_MAX, $this->service()->remainingConversions($user));
    }

    public function test_limited_plan_blocks_when_quota_is_exhausted(): void
    {
        $user = $this->userWithLimit(1);

        $this->assertTrue($this->service()->canConvert($user));

        app(UsageTrackingService::class)->record($user, 'svg_conversion');

        $this->assertFalse($this->service()->canConvert($user));
        $this->assertSame(0, $this->service()->remainingConversions($user));
    }

    public function test_max_file_size_is_capped_by_global_setting(): void
    {
        config(['imageconverter.max_upload_size_kb' => 512]);
        $user = $this->userWithLimit(10);

        $this->assertSame(512 * 1024, $this->service()->maxFileSizeBytes($user));
    }

    public function test_background_removal_requires_available_setting(): void
    {
        app(AppSettings::class)->setMany(['background_removal_available' => 0]);
        $user = $this->userWithLimit(10);

        $this->assertFalse($this->service()->canRemoveBackground($user));

        app(AppSettings::class)->setMany(['background_removal_available' => 1]);

        $this->assertTrue($this->service()->canRemoveBackground($user));
    }

    public function test_api_access_follows_plan_flag(): void
    {
        $yes = $this->userWithLimit(10);
        $no = User::factory()->create();
        Plan::create([
            'name' => 'Free',
            'slug' => 'free-'.uniqid(),
            'price' => 0,
            'billing_interval' => 'monthly',
            'monthly_conversion_limit' => 5,
            'max_file_size_kb' => 1024,
            'max_dimensions' => 4096,
            'background_removal_enabled' => false,
            'api_enabled' => false,
            'is_active' => true,
        ])->users()->save($no);

        $this->assertTrue($this->service()->canUseApi($yes));
        $this->assertFalse($this->service()->canUseApi($no));
    }
}