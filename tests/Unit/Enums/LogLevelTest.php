<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\LogLevel;

it('has the expected cases', function (): void {
    expect(LogLevel::cases())->toHaveCount(4)
        ->and(LogLevel::Debug->value)->toBe('debug')
        ->and(LogLevel::Info->value)->toBe('info')
        ->and(LogLevel::Warning->value)->toBe('warning')
        ->and(LogLevel::Error->value)->toBe('error');
});
