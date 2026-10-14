<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackgroundRemoval;

class AdminBackgroundRemovalController extends Controller
{
    public function index()
    {
        return view('admin.background-removals.index', [
            'removals' => BackgroundRemoval::with(['conversion.user'])->latest()->paginate(20),
        ]);
    }
}