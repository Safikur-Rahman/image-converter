<?php

use App\Http\Controllers\ConversionController;
use App\Http\Controllers\ConverterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\Admin\AdminBackgroundRemovalController;
use App\Http\Controllers\Admin\AdminConversionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFailedJobController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUsageController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/converter', [ConverterController::class, 'show'])->name('converter');
    Route::post('/converter', [ConverterController::class, 'store'])
        ->middleware('throttle:conversions')
        ->name('converter.store');

    Route::get('/conversions', [ConversionController::class, 'index'])->name('conversions.index');
    Route::get('/conversions/{conversion}', [ConversionController::class, 'show'])->name('conversions.show');
    Route::get('/conversions/{conversion}/status', [ConversionController::class, 'status'])->name('conversions.status');
    Route::post('/conversions/{conversion}/retry', [ConversionController::class, 'retry'])->name('conversions.retry');
    Route::delete('/conversions/{conversion}', [ConversionController::class, 'destroy'])->name('conversions.destroy');

    Route::get('/downloads/{conversion}/original', [DownloadController::class, 'original'])->name('downloads.original');
    Route::get('/downloads/{conversion}/preview-original', [DownloadController::class, 'previewOriginal'])->name('downloads.preview-original');
    Route::get('/downloads/{conversion}/svg', [DownloadController::class, 'svg'])->name('downloads.svg');
    Route::get('/downloads/{conversion}/preview-svg', [DownloadController::class, 'previewSvg'])->name('downloads.preview-svg');
    Route::get('/downloads/{conversion}/background-removed', [DownloadController::class, 'backgroundRemoved'])->name('downloads.background-removed');

    Route::get('/usage', UsageController::class)->name('usage');
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('/impersonation/stop', [AdminUserController::class, 'stopImpersonating'])->name('users.stop-impersonating');

    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');

    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');

    Route::get('/conversions', [AdminConversionController::class, 'index'])->name('conversions.index');
    Route::get('/conversions/{conversion}', [AdminConversionController::class, 'show'])->name('conversions.show');
    Route::post('/conversions/{conversion}/cancel', [AdminConversionController::class, 'cancel'])->name('conversions.cancel');
    Route::post('/conversions/{conversion}/retry', [AdminConversionController::class, 'retry'])->name('conversions.retry');
    Route::post('/conversions/retry-all-failed', [AdminConversionController::class, 'retryAllFailed'])->name('conversions.retry-all');

    Route::get('/background-removals', [AdminBackgroundRemovalController::class, 'index'])->name('background-removals.index');

    Route::get('/usage', [AdminUsageController::class, 'index'])->name('usage.index');

    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');

    Route::get('/failed-jobs', [AdminFailedJobController::class, 'index'])->name('failed-jobs.index');
    Route::post('/failed-jobs/{id}/retry', [AdminFailedJobController::class, 'retry'])->name('failed-jobs.retry');
    Route::delete('/failed-jobs/{id}', [AdminFailedJobController::class, 'destroy'])->name('failed-jobs.destroy');
    Route::post('/failed-jobs/flush', [AdminFailedJobController::class, 'flush'])->name('failed-jobs.flush');

    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';