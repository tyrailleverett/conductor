<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\EventRunStatus;
use HotReloadStudios\Conductor\EventFunction;
use HotReloadStudios\Conductor\Jobs\EventFunctionJob;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

final class PassingEventFunction extends EventFunction
{
    public function listenTo(): string
    {
        return 'test.event';
    }

    public function handle(array $payload): void {}
}

final class FailingEventFunction extends EventFunction
{
    public function listenTo(): string
    {
        return 'test.event';
    }

    public function handle(array $payload): void
    {
        throw new RuntimeException('Function failed intentionally.');
    }
}

it('executes the function and marks the run as completed', function (): void {
    $eventRun = ConductorEventRun::factory()->create([
        'function_class' => PassingEventFunction::class,
        'status' => EventRunStatus::Pending,
    ]);

    $job = new EventFunctionJob($eventRun->id, PassingEventFunction::class, []);
    $job->handle();

    $eventRun->refresh();
    expect($eventRun->status)->toBe(EventRunStatus::Completed)
        ->and($eventRun->duration_ms)->not->toBeNull();
});

it('marks the run as failed when the function throws', function (): void {
    $eventRun = ConductorEventRun::factory()->create([
        'function_class' => FailingEventFunction::class,
        'status' => EventRunStatus::Pending,
        'attempts' => 2,
    ]);

    $job = new EventFunctionJob($eventRun->id, FailingEventFunction::class, []);
    $job->tries = 3;

    expect(fn () => $job->handle())->toThrow(RuntimeException::class, 'Function failed intentionally.');

    $eventRun->refresh();
    expect($eventRun->status)->toBe(EventRunStatus::Failed)
        ->and($eventRun->error_message)->toBe('Function failed intentionally.');
});
