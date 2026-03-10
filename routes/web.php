<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Http\Controllers\DashboardController;
use HotReloadStudios\Conductor\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::prefix((string) config('conductor.path'))
    ->middleware(array_merge((array) config('conductor.middleware', ['web']), [Authorize::class]))
    ->group(function (): void {
        Route::get('{any?}', DashboardController::class)
            ->where('any', '^(?!api(?:/|$)|webhook(?:/|$)).*');
    });
