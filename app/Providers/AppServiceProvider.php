<?php

namespace App\Providers;

use App\Services\BackgroundRemoval\BackgroundRemovalService;
use App\Services\Conversion\ConversionProcessor;
use App\Services\Image\ImagePreprocessingService;
use App\Services\Storage\StorageService;
use App\Services\Svg\SvgConversionService;
use App\Services\Svg\SvgOptimizationService;
use App\Services\Svg\SvgTracingService;
use App\Services\Svg\SvgValidationService;
use App\Services\Usage\UsageLimitService;
use App\Services\Usage\UsageTrackingService;
use App\Support\AppSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppSettings::class);
        $this->app->singleton(StorageService::class);
        $this->app->singleton(BackgroundRemovalService::class);
        $this->app->singleton(UsageTrackingService::class);
        $this->app->singleton(ImagePreprocessingService::class);

        $this->app->singleton(SvgValidationService::class);
        $this->app->singleton(SvgOptimizationService::class);
        $this->app->singleton(SvgTracingService::class);

        $this->app->singleton(SvgConversionService::class, function ($app) {
            return new SvgConversionService(
                $app->make(SvgTracingService::class),
                $app->make(SvgValidationService::class),
                $app->make(SvgOptimizationService::class)
            );
        });

        $this->app->singleton(ConversionProcessor::class, function ($app) {
            return new ConversionProcessor(
                $app->make(SvgConversionService::class),
                $app->make(BackgroundRemovalService::class),
                $app->make(UsageTrackingService::class),
                $app->make(StorageService::class),
                $app->make(ImagePreprocessingService::class)
            );
        });

        $this->app->singleton(UsageLimitService::class, function ($app) {
            return new UsageLimitService(
                $app->make(AppSettings::class),
                $app->make(UsageTrackingService::class)
            );
        });
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $view->with('limits', app(UsageLimitService::class));
                $view->with('bgAvailability', app(\App\Services\BackgroundRemoval\BackgroundRemovalService::class)->isAvailable());
            }
        });
    }
}