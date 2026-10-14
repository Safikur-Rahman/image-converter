<?php

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversionController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->conversions()
            ->with('files')
            ->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where('original_filename', 'like', '%'.$search.'%');
        }

        if ($status = $request->query('status')) {
            if (ConversionStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return view('conversions.index', [
            'conversions' => $query->paginate(12)->withQueryString(),
            'statuses' => ConversionStatus::cases(),
            'filters' => $request->only(['search', 'status', 'from', 'to']),
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $conversion = Conversion::where('uuid', $uuid)->with(['files', 'svgConversion', 'backgroundRemoval'])->firstOrFail();

        $this->authorize('view', $conversion);

        return view('conversions.show', [
            'conversion' => $conversion,
        ]);
    }

    public function status(Request $request, string $uuid): JsonResponse
    {
        $conversion = Conversion::where('uuid', $uuid)->with('files')->firstOrFail();

        $this->authorize('view', $conversion);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $conversion->status->value,
                'status_label' => $conversion->status->label(),
                'has_svg' => $conversion->svgFile() !== null,
                'has_original' => $conversion->originalFile() !== null,
                'has_background_removed' => $conversion->backgroundRemovedFile() !== null,
                'error_message' => $conversion->error_message,
            ],
        ]);
    }

    public function destroy(Request $request, string $uuid): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();

        $this->authorize('delete', $conversion);

        app(\App\Services\Storage\StorageService::class)->deleteAllForConversion($conversion);

        $conversion->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Conversion deleted.', 'data' => null]);
        }

        return back()->with('flash', 'Conversion deleted.');
    }

    public function retry(Request $request, string $uuid): \Illuminate\Http\RedirectResponse
    {
        $conversion = Conversion::where('uuid', $uuid)->firstOrFail();

        $this->authorize('update', $conversion);

        if ($conversion->status !== ConversionStatus::Failed) {
            return back()->with('flash', 'Only failed conversions can be retried.');
        }

        $conversion->update([
            'status' => ConversionStatus::Pending,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        \App\Jobs\ProcessImageConversion::dispatch($conversion);

        return back()->with('flash', 'Conversion queued for retry.');
    }
}