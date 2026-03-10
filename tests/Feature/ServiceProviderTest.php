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

it('casts webhook rate limit env values to integers', function (): void {
    $originalWebhookRateLimit = getenv('CONDUCTOR_WEBHOOK_RATE_LIMIT');

    putenv('CONDUCTOR_WEBHOOK_RATE_LIMIT=120');

    $config = require __DIR__.'/../../config/conductor.php';

    expect($config['webhook_rate_limit'])->toBeInt()->toBe(120);

    putenv($originalWebhookRateLimit === false
        ? 'CONDUCTOR_WEBHOOK_RATE_LIMIT'
        : 'CONDUCTOR_WEBHOOK_RATE_LIMIT='.$originalWebhookRateLimit);
});

it('does not leave skeleton placeholders in composer metadata', function (): void {
    try {
        /** @var array<string, mixed> $composer */
        $composer = json_decode(
            file_get_contents(__DIR__.'/../../composer.json') ?: '',
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    } catch (JsonException $exception) {
        test()->fail($exception->getMessage());
    }

    $composerMetadata = json_encode([
        'keywords' => $composer['keywords'] ?? [],
        'homepage' => $composer['homepage'] ?? null,
        'authors' => $composer['authors'] ?? [],
    ], JSON_THROW_ON_ERROR);

    expect($composerMetadata)
        ->not->toContain(':vendor_name')
        ->not->toContain(':vendor_slug')
        ->not->toContain(':package_slug')
        ->not->toContain(':author_name');
});
