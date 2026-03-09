<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

use HotReloadStudios\Conductor\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ConductorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('conductor')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Conductor::class);
    }

    public function packageBooted(): void
    {
        Route::prefix((string) config('conductor.path').'/api')
            ->middleware(array_merge((array) config('conductor.middleware', ['web']), [Authorize::class]))
            ->group(__DIR__.'/../routes/api.php');

        Route::prefix((string) config('conductor.path').'/webhook')
            ->middleware((array) config('conductor.middleware', ['web']))
            ->group(__DIR__.'/../routes/webhook.php');

        if (! $this->app->environment('local') && ! $this->hasAuthCallbackConfigured()) {
            Log::warning('Conductor dashboard and API routes will return 403 until Conductor::auth() is configured.');
        }
    }

    private function hasAuthCallbackConfigured(): bool
    {
        $property = new ReflectionProperty(Conductor::class, 'authUsing');

        return $property->getValue() !== null;
    }
}
