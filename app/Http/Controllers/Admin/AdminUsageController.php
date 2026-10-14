<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\UsageRecord;

class AdminUsageController extends Controller
{
    public function index()
    {
        return view('admin.usage.index', [
            'records' => UsageRecord::with(['user'])->latest()->paginate(20),
            'downloads' => Download::with(['conversion', 'user'])->latest()->paginate(20),
        ]);
    }
}