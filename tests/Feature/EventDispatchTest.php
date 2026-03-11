<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\EventRunStatus;
use HotReloadStudios\Conductor\EventFunction;
use HotReloadStudios\Conductor\Jobs\EventFunctionJob;
use HotReloadStudios\Conductor\Models\ConductorEvent;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

final class TestUserCreatedFunction extends EventFunction
{
    public function listenTo(): string
    {
        return 'user.created';
    }

    public function handle(array $payload): void {}
}

final class TestOrderCreatedFunction extends EventFunction
{
    public function listenTo(): string
    {
        return 'order.created';
    }

    public function handle(array $payload): void {}
}

final class TestSecondUserCreatedFunction extends EventFunction
{
    public function listenTo(): string
    {
        return 'user.created';
    }

    public function handle(array $payload): void {}
}

it('creates an event record when dispatched', function (): void {
    Queue::fake();
    config()->set('conductor.functions', [TestUserCreatedFunction::class]);

    ConductorEvent::dispatch('user.created', ['key' => 'value']);

    expect(ConductorEvent::count())->toBe(1);
    $event = ConductorEvent::first();
    expect($event->name)->toBe('user.created');
});

it('dispatches listener jobs for matching functions', function (): void {
    Queue::fake();
    config()->set('conductor.functions', [
        TestUserCreatedFunction::class,
        TestSecondUserCreatedFunction::class,
    ]);

    ConductorEvent::dispatch('user.created', ['key' => 'value']);

    expect(ConductorEventRun::count())->toBe(2);

    $runs = ConductorEventRun::all();
    foreach ($runs as $run) {
        expect($run->status)->toBe(EventRunStatus::Pending);
    }

    Queue::assertPushed(EventFunctionJob::class, 2);
});

it('does not dispatch for non-matching functions', function (): void {
    Queue::fake();
    config()->set('conductor.functions', [TestOrderCreatedFunction::class]);

    ConductorEvent::dispatch('user.created', ['key' => 'value']);

    expect(ConductorEventRun::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('redacts sensitive keys in event payload', function (): void {
    Queue::fake();
    config()->set('conductor.functions', []);
    config()->set('conductor.redact_keys', ['password']);

    ConductorEvent::dispatch('user.created', ['password' => 'secret123', 'name' => 'Alice']);

    $event = ConductorEvent::first();
    expect($event->payload['password'])->toBe('[REDACTED]')
        ->and($event->payload['name'])->toBe('Alice');
});
