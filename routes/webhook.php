<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Http\Controllers\WebhookController;
use HotReloadStudios\Conductor\Http\Middleware\WebhookRateLimit;
use Illuminate\Support\Facades\Route;

Route::post('{source}', WebhookController::class)
    ->middleware(WebhookRateLimit::class);
