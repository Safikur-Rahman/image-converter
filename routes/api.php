<?php

use App\Http\Controllers\Api\V1\ConversionController;
use App\Http\Controllers\Api\V1\MetaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('/me', [MetaController::class, 'profile'])->name('me');
    Route::get('/plans', [MetaController::class, 'plans'])->name('plans');
    Route::get('/capabilities', [MetaController::class, 'capabilities'])->name('capabilities');

    Route::get('/tokens', [MetaController::class, 'tokens'])->name('tokens');
    Route::post('/tokens', [MetaController::class, 'createToken'])->middleware('throttle:api')->name('tokens.create');

    Route::prefix('conversions')->group(function () {
        Route::get('/', [ConversionController::class, 'index'])->middleware('throttle:api')->name('conversions.index');
        Route::post('/', [ConversionController::class, 'store'])->middleware('throttle:api')->name('conversions.store');
        Route::get('/{conversion}', [ConversionController::class, 'show'])->name('conversions.show');
        Route::get('/{conversion}/status', [ConversionController::class, 'status'])->name('conversions.status');
        Route::get('/{conversion}/download/{type}', [ConversionController::class, 'download'])->whereIn('type', ['svg', 'original', 'background_removed'])->name('conversions.download');
        Route::delete('/{conversion}', [ConversionController::class, 'destroy'])->name('conversions.destroy');
    })->where('conversion', '[0-9a-fA-F\-]{36}');
});