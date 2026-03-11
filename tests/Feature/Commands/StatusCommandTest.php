<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('outputs queue driver info', function (): void {
    $this->artisan('conductor:status')
        ->expectsOutputToContain('Queue connection:')
        ->assertExitCode(0);
});

it('shows worker status table', function (): void {
    ConductorWorker::factory()->create([
        'worker_name' => 'worker-1234',
        'last_heartbeat_at' => now(),
    ]);

    ConductorWorker::factory()->stale()->create([
        'worker_name' => 'worker-5678',
        'last_heartbeat_at' => now()->subMinutes(10),
    ]);

    $this->artisan('conductor:status')
        ->expectsOutputToContain('worker-1234')
        ->expectsOutputToContain('worker-5678');
});

it('exits with code 1 when workers are offline', function (): void {
    ConductorWorker::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(10),
    ]);

    $this->artisan('conductor:status')->assertExitCode(1);
});

it('exits with code 0 when all workers are healthy', function (): void {
    ConductorWorker::factory()->create([
        'last_heartbeat_at' => now(),
    ]);

    $this->artisan('conductor:status')->assertExitCode(0);
});

it('shows job counts by status', function (): void {
    ConductorJob::factory()->count(3)->create();

    $this->artisan('conductor:status')
        ->expectsOutputToContain('pending')
        ->assertExitCode(0);
});
