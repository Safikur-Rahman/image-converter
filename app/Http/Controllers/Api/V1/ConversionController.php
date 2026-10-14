<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateConversionAction;
use App\Enums\ConversionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversionRequest;
use App\Http\Resources\ConversionResource;
use App\Jobs\ProcessImageConversion;
use App\Models\Conversion;
use App\Services\Storage\StorageService;
use App\Services\Usage\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ConversionController extends Controller
{
    public function __construct(
        private StorageService $storage,
        private UsageLimitService $limits,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversions = $request->user()
            ->conversions()
            ->with('files')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Conversions retrieved.',
            'data' => [
                'items' => ConversionResource::collection($conversions),
                'pagination' => [
                    'total' => $conversions->total(),
                    'per_page' => $conversions->perPage(),
                    'current_page' => $conversions->currentPage(),
                    'last_page' => $conversions->lastPage(),
                ],
            ],
        ]);
    }

    public function store(StoreConversionRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->limits->canConvert($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached your monthly conversion limit.',
                'data' => null,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if ($request->boolean('remove_background')) {
            $bgService = app(\App\Services\BackgroundRemoval\BackgroundRemovalService::class);
            if (!$bgService->isAvailable() || !$user->effectivePlan()->background_removal_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Background removal is not available for your plan.',
                    'data' => null,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            $conversion = DB::transaction(function () use ($request, $user) {
                $action = app(CreateConversionAction::class);

                return $action->execute(
                    $user,
                    $request->file('image'),
                    $request->boolean('remove_background'),
                    $request->input('conversion_type', 'color'),
                    $request->input('quality', config('imageconverter.default_svg_quality', 'high')),
                    $request->input('path_optimization', 'balanced'),
                );
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => ['errors' => $e->errors()],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('API conversion creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while processing your image.',
                'data' => null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        ProcessImageConversion::dispatch($conversion);

        return response()->json([
            'success' => true,
            'message' => 'Conversion started.',
            'data' => new ConversionResource($conversion->load('files')),
        ], Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $conversion = $request->user()->conversions()->with('files')->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Conversion retrieved.',
            'data' => new ConversionResource($conversion),
        ]);
    }

    public function status(Request $request, string $uuid): JsonResponse
    {
        $conversion = $request->user()->conversions()->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Status retrieved.',
            'data' => [
                'uuid' => $conversion->uuid,
                'status' => $conversion->status->value,
                'status_label' => $conversion->status->label(),
            ],
        ]);
    }

    public function download(Request $request, string $uuid, string $type = 'svg'): Response
    {
        $conversion = $request->user()->conversions()->where('uuid', $uuid)->firstOrFail();

        if (!in_array($type, ['svg', 'original', 'background_removed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported download type.',
                'data' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = match ($type) {
            'svg' => $conversion->svgFile(),
            'original' => $conversion->originalFile(),
            default => $conversion->backgroundRemovedFile(),
        };

        if ($file === null) {
            return response()->json([
                'success' => false,
                'message' => 'This file is not available yet.',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        $path = $this->storage->disk()->path($file->path);
        if (!is_file($path)) {
            $temporary = $this->storage->temporaryUrl($file->path, 5);
            if ($temporary) {
                return response()->redirectTo($temporary);
            }

            return response()->json([
                'success' => false,
                'message' => 'The file is no longer available.',
                'data' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->download($path, $file->filename);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $conversion = $request->user()->conversions()->where('uuid', $uuid)->firstOrFail();

        $this->storage->deleteAllForConversion($conversion);
        $conversion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversion deleted.',
            'data' => null,
        ]);
    }
}