<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversionStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversion;
use Illuminate\Http\Request;

class AdminConversionController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversion::with(['user'])->latest();

        if ($status = $request->query('status')) {
            if (ConversionStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', '%'.$search.'%')
                    ->orWhere('uuid', 'like', '%'.$search.'%');
            });
        }

        return view('admin.conversions.index', [
            'conversions' => $query->paginate(20)->withQueryString(),
            'statuses' => ConversionStatus::cases(),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Conversion $conversion)
    {
        $conversion->load(['user', 'files', 'svgConversion', 'backgroundRemoval', 'downloads' => fn ($q) => $q->latest()->limit(20)]);

        return view('admin.conversions.show', compact('conversion'));
    }

    public function cancel(Conversion $conversion)
    {
        if ($conversion->status->isTerminal()) {
            return back()->with('flash', 'This conversion cannot be cancelled.');
        }

        $conversion->update(['status' => ConversionStatus::Cancelled]);

        return back()->with('flash', 'Conversion cancelled.');
    }

    public function retry(Conversion $conversion)
    {
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

    public function retryAllFailed()
    {
        $ids = Conversion::where('status', ConversionStatus::Failed)->pluck('id');

        foreach ($ids as $id) {
            Conversion::find($id)?->update([
                'status' => ConversionStatus::Pending,
                'error_message' => null,
                'started_at' => null,
                'completed_at' => null,
            ]);
            \App\Jobs\ProcessImageConversion::dispatch(Conversion::find($id));
        }

        return back()->with('flash', count($ids).' failed conversions requeued.');
    }
}