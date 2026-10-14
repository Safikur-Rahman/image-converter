<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;

class AdminSubscriptionController extends Controller
{
    public function index()
    {
        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::with(['user', 'plan'])->latest()->paginate(20),
        ]);
    }
}