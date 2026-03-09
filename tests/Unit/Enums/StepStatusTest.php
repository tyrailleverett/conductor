<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\StepStatus;

it('has the expected cases', function (): void {
    expect(StepStatus::cases())->toHaveCount(5)
        ->and(StepStatus::Pending->value)->toBe('pending')
        ->and(StepStatus::Running->value)->toBe('running')
        ->and(StepStatus::Completed->value)->toBe('completed')
        ->and(StepStatus::Failed->value)->toBe('failed')
        ->and(StepStatus::Skipped->value)->toBe('skipped');
});

it('identifies terminal statuses', function (): void {
    expect(StepStatus::Completed->isTerminal())->toBeTrue()
        ->and(StepStatus::Failed->isTerminal())->toBeTrue()
        ->and(StepStatus::Skipped->isTerminal())->toBeTrue()
        ->and(StepStatus::Pending->isTerminal())->toBeFalse()
        ->and(StepStatus::Running->isTerminal())->toBeFalse();
});
