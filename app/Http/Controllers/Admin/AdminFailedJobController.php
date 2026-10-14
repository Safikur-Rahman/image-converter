<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class AdminFailedJobController extends Controller
{
    public function index()
    {
        $jobs = \Illuminate\Support\Facades\Queue::failed()->all();
        usort($jobs, fn ($a, $b) => $b->failed_at <=> $a->failed_at);

        return view('admin.failed-jobs.index', compact('jobs'));
    }

    public function retry(string $id)
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('flash', 'Job queued for retry.');
    }

    public function destroy(string $id)
    {
        \Illuminate\Support\Facades\Queue::forget($id);

        return back()->with('flash', 'Failed job removed.');
    }

    public function flush()
    {
        \Illuminate\Support\Facades\Queue::flush();

        return back()->with('flash', 'All failed jobs cleared.');
    }
}