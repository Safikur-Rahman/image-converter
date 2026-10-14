<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\BackgroundRemoval\BackgroundRemovalService;
use App\Services\Usage\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MetaController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $limits = app(UsageLimitService::class);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => new PlanResource($user->effectivePlan()),
                'usage' => [
                    'conversions_this_month' => app(\App\Services\Usage\UsageTrackingService::class)->countForAction($user, 'svg_conversion'),
                    'remaining_conversions' => $limits->remainingConversions($user),
                ],
            ],
        ]);
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Plans retrieved.',
            'data' => PlanResource::collection(Plan::where('is_active', true)->orderBy('price')->get()),
        ]);
    }

    public function capabilities(Request $request): JsonResponse
    {
        $user = $request->user();
        $bg = app(BackgroundRemovalService::class);
        $limits = app(UsageLimitService::class);

        return response()->json([
            'success' => true,
            'data' => [
                'background_removal_available' => $bg->isAvailable() && $user->effectivePlan()->background_removal_enabled,
                'max_file_size_bytes' => $limits->maxFileSizeBytes($user),
                'max_dimensions' => $limits->maxDimensions($user),
                'allowed_formats' => app(\App\Support\AppSettings::class)->allowedFormats(),
                'remaining_conversions' => $limits->remainingConversions($user),
            ],
        ]);
    }

    public function tokens(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'created_at'])->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'abilities' => $t->abilities,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function createToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $limits = app(UsageLimitService::class);

        if (!$limits->canUseApi($user)) {
            return response()->json([
                'success' => false,
                'message' => 'API access is not available on your current plan.',
                'data' => null,
            ], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $plain = Str::random(64);

        $token = $user->createToken($request->input('name'));

        // Laravel Sanctum uses a random plain token; return a durable secret once.
        return response()->json([
            'success' => true,
            'message' => 'Token created. Store it now — it will not be shown again.',
            'data' => [
                'token' => $token->plainTextToken,
                'name' => $token->accessToken->name,
            ],
        ], 201);
    }
}