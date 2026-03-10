<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts status to JobStatus enum', function (): void {
    $job = ConductorJob::factory()->create();

    expect($job->status)->toBeInstanceOf(JobStatus::class);
});

it('casts tags and payload to arrays', function (): void {
    $job = ConductorJob::factory()->withTags(['billing', 'invoice'])->create([
        'payload' => ['foo' => 'bar'],
    ]);

    expect($job->tags)->toBeArray()
        ->and($job->payload)->toBeArray();
});

it('determines cancellability for pending jobs', function (): void {
    $job = ConductorJob::factory()->create(['status' => JobStatus::Pending]);

    expect($job->isCancellable())->toBeTrue();
});

it('determines cancellability for running jobs with cancellable_at', function (): void {
    $job = ConductorJob::factory()->running()->create(['cancellable_at' => now()]);

    expect($job->isCancellable())->toBeTrue();
});

it('is not cancellable when running without cancellable_at', function (): void {
    $job = ConductorJob::factory()->running()->create(['cancellable_at' => null]);

    expect($job->isCancellable())->toBeFalse();
});

it('resolves route key by uuid', function (): void {
    $job = new ConductorJob;

    expect($job->getRouteKeyName())->toBe('uuid');
});

it('has a logs relationship', function (): void {
    $job = ConductorJob::factory()->create();
    ConductorJobLog::factory()->count(3)->create(['job_id' => $job->id]);

    expect($job->logs)->toHaveCount(3)
        ->each->toBeInstanceOf(ConductorJobLog::class);
});

it('scopes by status', function (): void {
    ConductorJob::factory()->create(['status' => JobStatus::Pending]);
    ConductorJob::factory()->running()->create();
    ConductorJob::factory()->completed()->create();

    expect(ConductorJob::withStatus(JobStatus::Pending)->count())->toBe(1)
        ->and(ConductorJob::withStatus(JobStatus::Running)->count())->toBe(1);
});

it('scopes by queue', function (): void {
    ConductorJob::factory()->create(['queue' => 'default']);
    ConductorJob::factory()->create(['queue' => 'high']);
    ConductorJob::factory()->create(['queue' => 'high']);

    expect(ConductorJob::onQueue('high')->count())->toBe(2)
        ->and(ConductorJob::onQueue('default')->count())->toBe(1);
});

it('scopes failed jobs', function (): void {
    ConductorJob::factory()->create(['status' => JobStatus::Pending]);
    ConductorJob::factory()->failed()->create();
    ConductorJob::factory()->failed()->create();

    expect(ConductorJob::failed()->count())->toBe(2);
});

it('scopes by tag', function (): void {
    ConductorJob::factory()->withTags(['billing'])->create();
    ConductorJob::factory()->withTags(['billing'])->create();
    ConductorJob::factory()->withTags(['invoice'])->create();

    expect(ConductorJob::withTag('billing')->count())->toBe(2)
        ->and(ConductorJob::withTag('invoice')->count())->toBe(1);
});
