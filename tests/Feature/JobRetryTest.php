<?php

// phpcs:ignoreFile

declare(strict_types=1);

use HotReloadStudios\Conductor\Concerns\Trackable;
use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Services\JobRetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

final class JobRetryTrackableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function handle(): void {}
}

it('retries a failed job', function (): void {
    $originalJob = new JobRetryTrackableJob();
    $encrypted = Crypt::encryptString(serialize($originalJob));

    $conductorJob = ConductorJob::factory()->failed()->create([
        'class' => JobRetryTrackableJob::class,
        'payload' => ['display' => [], 'retry' => $encrypted],
        'queue' => 'default',
        'connection' => 'sync',
        'attempts' => 1,
    ]);

    $retryService = app(JobRetryService::class);
    $retryService->retry($conductorJob);

    $conductorJob->refresh();

    expect(ConductorJob::count())->toBe(1)
        ->and($conductorJob->status)->toBe(JobStatus::Completed)
        ->and($conductorJob->failed_at)->toBeNull()
        ->and($conductorJob->error_message)->toBeNull()
        ->and($conductorJob->attempts)->toBe(2)
        ->and($conductorJob->completed_at)->not->toBeNull();
});

it('rejects retry on a non-failed job', function (): void {
    $conductorJob = ConductorJob::factory()->completed()->create();

    $retryService = app(JobRetryService::class);

    expect(fn () => $retryService->retry($conductorJob))->toThrow(InvalidArgumentException::class);
});
