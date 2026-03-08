<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\ConductorServiceProvider;

it('boots the service provider without errors', function (): void {
    expect($this->app->providerIsLoaded(ConductorServiceProvider::class))->toBeTrue();
});

it('registers the conductor config', function (): void {
    expect(config('conductor.path'))->toBe('conductor')
        ->and(config('conductor.functions'))->toBe([]);
});

it('merges published config with defaults', function (): void {
    config()->set('conductor', array_merge(config('conductor'), ['path' => 'ops']));

    expect(config('conductor.path'))->toBe('ops')
        ->and(config('conductor.queue.queue'))->toBe('conductor')
        ->and(config('conductor.functions'))->toBe([]);
});
