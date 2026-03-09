<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\JobStatus;

it('has the expected cases', function (): void {
    expect(JobStatus::cases())->toHaveCount(6)
        ->and(JobStatus::Pending->value)->toBe('pending')
        ->and(JobStatus::Running->value)->toBe('running')
        ->and(JobStatus::Completed->value)->toBe('completed')
        ->and(JobStatus::Failed->value)->toBe('failed')
        ->and(JobStatus::CancellationRequested->value)->toBe('cancellation_requested')
        ->and(JobStatus::Cancelled->value)->toBe('cancelled');
});

it('identifies terminal statuses', function (): void {
    expect(JobStatus::Completed->isTerminal())->toBeTrue()
        ->and(JobStatus::Failed->isTerminal())->toBeTrue()
        ->and(JobStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(JobStatus::Pending->isTerminal())->toBeFalse()
        ->and(JobStatus::Running->isTerminal())->toBeFalse()
        ->and(JobStatus::CancellationRequested->isTerminal())->toBeFalse();
});
