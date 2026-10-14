<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;

class AdminLogController extends Controller
{
    public function index()
    {
        return view('admin.logs.index', [
            'logs' => SystemLog::with(['user'])->latest()->paginate(30),
        ]);
    }
}