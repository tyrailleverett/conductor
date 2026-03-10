<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\ConductorServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->setConductorAuth();
});

it('allows all access in local environment', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'local');

    $this->get('/conductor')->assertSuccessful();
});

it('blocks access in production when no gate is configured', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');

    $this->get('/conductor')->assertForbidden();
});

it('emits a warning in non-local environments when no gate is configured', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');
    Log::spy();

    $provider = new ConductorServiceProvider($this->app);
    $provider->packageBooted();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Conductor dashboard and API routes will return 403 until Conductor::auth() is configured.');
});

it('allows access when custom gate returns true', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');
    $this->setConductorAuth(static fn (Request $request): bool => true);

    $this->get('/conductor')->assertSuccessful();
});

it('blocks access when custom gate returns false', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');
    $this->setConductorAuth(static fn (Request $request): bool => false);

    $this->get('/conductor')->assertForbidden();
});
