<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Support\ConductorContext;

afterEach(function (): void {
    ConductorContext::clear();
});

it('starts with no active context', function (): void {
    expect(ConductorContext::isActive())->toBeFalse()
        ->and(ConductorContext::get())->toBeNull();
});

it('sets and gets the current job id', function (): void {
    ConductorContext::set(42);

    expect(ConductorContext::get())->toBe(42)
        ->and(ConductorContext::isActive())->toBeTrue();
});

it('clears the context', function (): void {
    ConductorContext::set(99);
    ConductorContext::clear();

    expect(ConductorContext::get())->toBeNull()
        ->and(ConductorContext::isActive())->toBeFalse();
});
