<?php

namespace Tests\Feature;

use App\Models\Conversion;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 12,
            'billing_interval' => 'monthly',
            'monthly_conversion_limit' => 200,
            'max_file_size_kb' => 10240,
            'max_dimensions' => 8000,
            'background_removal_enabled' => false,
            'api_enabled' => true,
            'is_active' => true,
        ]);
    }

    private function userWithToken(): User
    {
        $user = User::factory()->create(['plan_id' => $this->plan->id]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_profile_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_read_profile_and_plans(): void
    {
        $user = $this->userWithToken();

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_user_can_create_api_token(): void
    {
        $user = $this->userWithToken();

        $this->postJson('/api/v1/tokens', ['name' => 'ci-test'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'ci-test', 'tokenable_id' => $user->id]);
    }

    public function test_valid_upload_returns_accepted_and_runs_pipeline(): void
    {
        $user = $this->userWithToken();

        $response = $this->postJson('/api/v1/conversions', [
            'image' => UploadedFile::fake()->image('logo.png', 90, 60),
            'conversion_type' => 'color',
            'quality' => 'high',
            'path_optimization' => 'balanced',
        ])->assertStatus(202)
            ->assertJsonPath('success', true);

        $uuid = data_get(json_decode($response->getContent(), true), 'data.uuid');
        $this->assertNotNull($uuid);

        $conversion = Conversion::where('uuid', $uuid)->first();
        $this->assertNotNull($conversion);
        $this->assertSame('completed', $conversion->fresh()->status->value);

        $this->getJson("/api/v1/conversions/$uuid/status")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->getJson('/api/v1/conversions')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this->deleteJson("/api/v1/conversions/$uuid")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('conversions', ['id' => $conversion->id]);
    }

    public function test_invalid_upload_is_rejected(): void
    {
        $this->userWithToken();

        $this->postJson('/api/v1/conversions', [
            'image' => UploadedFile::fake()->create('doc.txt', 10),
        ])->assertUnprocessable();
    }

    public function test_user_cannot_access_another_users_conversion(): void
    {
        $this->userWithToken();

        $other = User::factory()->create(['plan_id' => $this->plan->id]);
        $conversion = Conversion::factory()->create(['user_id' => $other->id, 'uuid' => (string) Str::uuid()]);

        $this->getJson("/api/v1/conversions/{$conversion->uuid}")
            ->assertNotFound();
    }
}