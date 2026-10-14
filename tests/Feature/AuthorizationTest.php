<?php

namespace Tests\Feature;

use App\Models\Conversion;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pro = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 12,
            'billing_interval' => 'monthly',
            'monthly_conversion_limit' => 200,
            'max_file_size_kb' => 10240,
            'max_dimensions' => 8000,
            'background_removal_enabled' => true,
            'api_enabled' => true,
            'is_active' => true,
        ]);
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $regular = User::factory()->create(['role' => 'user']);

        $this->actingAs($regular)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_view_any_conversion(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'plan_id' => $this->pro->id]);
        $owner = User::factory()->create(['plan_id' => $this->pro->id]);
        $conversion = Conversion::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)->get(route('conversions.show', $conversion->uuid))->assertOk();
    }

    public function test_user_cannot_delete_another_users_conversion(): void
    {
        $owner = User::factory()->create(['plan_id' => $this->pro->id]);
        $intruder = User::factory()->create(['plan_id' => $this->pro->id]);
        $conversion = Conversion::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->delete(route('conversions.destroy', $conversion->uuid))
            ->assertForbidden();

        $this->assertDatabaseHas('conversions', ['id' => $conversion->id]);
    }

    public function test_owner_can_delete_own_conversion(): void
    {
        $owner = User::factory()->create(['plan_id' => $this->pro->id]);
        $conversion = Conversion::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('conversions.destroy', $conversion->uuid))
            ->assertRedirect();

        $this->assertDatabaseMissing('conversions', ['id' => $conversion->id]);
    }

    public function test_conversion_can_be_deleted_via_json_request(): void
    {
        $owner = User::factory()->create();
        $conversion = Conversion::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson(route('conversions.destroy', $conversion->uuid))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('conversions', ['id' => $conversion->id]);
    }

    public function test_only_owner_can_retry_failed_conversion(): void
    {
        $owner = User::factory()->create(['plan_id' => $this->pro->id]);
        $intruder = User::factory()->create(['plan_id' => $this->pro->id]);
        $conversion = Conversion::factory()->create([
            'user_id' => $owner->id,
            'status' => \App\Enums\ConversionStatus::Failed,
        ]);

        $this->actingAs($intruder)
            ->post(route('conversions.retry', $conversion->uuid))
            ->assertForbidden();
    }

    public function test_guest_cannot_browse_conversions(): void
    {
        $this->get(route('conversions.index'))->assertRedirect(route('login'));
    }
}