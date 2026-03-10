<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Concerns\Trackable;
use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

final class JobTrackingBasicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function handle(): void {}
}

final class JobTrackingFailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function handle(): void
    {
        throw new RuntimeException('Job failed intentionally.');
    }
}

final class JobTrackingLoggingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function handle(): void
    {
        Log::info('test message');
    }
}

final class JobTrackingTaggedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function conductorTags(): array
    {
        return ['billing', 'invoice'];
    }

    public function handle(): void {}
}

final class JobTrackingRedactedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function __construct(public string $password = 'secret') {}

    public function handle(): void {}
}

it('creates a conductor_jobs record when a trackable job is dispatched', function (): void {
    dispatch(new JobTrackingBasicJob());

    expect(ConductorJob::count())->toBe(1);
});

it('updates status to completed when job finishes', function (): void {
    dispatch(new JobTrackingBasicJob());

    $job = ConductorJob::first();

    expect($job->status)->toBe(JobStatus::Completed)
        ->and($job->completed_at)->not->toBeNull()
        ->and($job->duration_ms)->not->toBeNull()
        ->and($job->started_at)->not->toBeNull();
});

it('updates status to failed when job throws', function (): void {
    expect(fn () => dispatch(new JobTrackingFailingJob()))->toThrow(RuntimeException::class);

    $job = ConductorJob::first();

    expect($job->status)->toBe(JobStatus::Failed)
        ->and($job->error_message)->toBe('Job failed intentionally.')
        ->and($job->stack_trace)->not->toBeNull();
});

it('captures log output during job execution', function (): void {
    dispatch(new JobTrackingLoggingJob());

    $log = ConductorJobLog::first();

    expect($log)->not->toBeNull()
        ->and($log->message)->toContain('test message');
});

it('redacts sensitive keys in payload', function (): void {
    dispatch(new JobTrackingRedactedJob('my-secret-password'));

    $job = ConductorJob::first();

    expect($job->payload['display']['password'])->toBe('[REDACTED]');
});

it('stores tags from conductorTags method', function (): void {
    dispatch(new JobTrackingTaggedJob());

    $job = ConductorJob::first();

    expect($job->tags)->toBe(['billing', 'invoice']);
});

it('preserves conductor job id across serialization', function (): void {
    dispatch(new JobTrackingBasicJob());

    expect(ConductorJob::first()->id)->not->toBeNull();
});
