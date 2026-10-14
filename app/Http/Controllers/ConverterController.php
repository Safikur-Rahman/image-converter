<?php

namespace App\Http\Controllers;

use App\Actions\CreateConversionAction;
use App\Http\Requests\StoreConversionRequest;
use App\Jobs\ProcessImageConversion;
use App\Services\BackgroundRemoval\BackgroundRemovalService;
use App\Services\Usage\UsageLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConverterController extends Controller
{
    public function show(Request $request, UsageLimitService $limits, BackgroundRemovalService $backgroundRemoval)
    {
        $user = $request->user();

        if (!$limits->canConvert($user)) {
            return redirect()->route('subscription.index')
                ->with('flash', 'You have reached your monthly conversion limit. Upgrade your plan to continue.');
        }

        return view('converter', [
            'maxFileSizeBytes' => $limits->maxFileSizeBytes($user),
            'maxFileSizeMb' => round($limits->maxFileSizeBytes($user) / 1024 / 1024, 1),
            'maxDimensions' => $limits->maxDimensions($user),
            'remaining' => $limits->remainingConversions($user),
            'backgroundRemovalAvailable' => $backgroundRemoval->isAvailable(),
            'backgroundRemovalEnabled' => $user->effectivePlan()->background_removal_enabled,
        ]);
    }

    public function store(StoreConversionRequest $request, CreateConversionAction $create)
    {
        $user = $request->user();
        $limits = app(UsageLimitService::class);

        if (!$limits->canConvert($user)) {
            return back()->withErrors(['limit' => 'You have reached your monthly conversion limit.'])->withInput();
        }

        try {
            $conversion = DB::transaction(function () use ($create, $user, $request) {
                return $create->execute(
                    $user,
                    $request->file('image'),
                    $request->boolean('remove_background'),
                    $request->input('conversion_type', 'color'),
                    $request->input('quality', config('imageconverter.default_svg_quality', 'high')),
                    $request->input('path_optimization', 'balanced'),
                );
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Conversion creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['image' => 'Something went wrong while processing your image. Please try again.'])->withInput();
        }

        ProcessImageConversion::dispatch($conversion);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conversion started.',
                'data' => ['uuid' => $conversion->uuid, 'status' => $conversion->status->value],
            ]);
        }

        return redirect()->route('conversions.show', ['conversion' => $conversion->uuid])
            ->with('conversion_started', true);
    }
}