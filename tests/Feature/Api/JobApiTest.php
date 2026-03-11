<?php

// phpcs:ignoreFile

declare(strict_types=1);

use HotReloadStudios\Conductor\Concerns\Trackable;
use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

final class JobApiTrackableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function handle(): void {}
}

it('returns a paginated list of jobs', function (): void {
    ConductorJob::factory()->count(19)->create();
    ConductorJob::factory()->create(['stack_trace' => 'sensitive stack trace']);

    $response = $this->getJson('/conductor/api/jobs')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonStructure(['data', 'meta', 'links']);

    expect($response->json('data.0'))->not->toHaveKey('stack_trace');
});

it('filters jobs by status', function (): void {
    ConductorJob::factory()->count(3)->failed()->create();
    ConductorJob::factory()->count(2)->completed()->create();

    $response = $this->getJson('/conductor/api/jobs?status=failed')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(3);
});

it('rejects an invalid status filter', function (): void {
    $this->getJson('/conductor/api/jobs?status=not-a-status')
        ->assertStatus(422)
        ->assertJson(['message' => 'The selected status is invalid.']);
});

it('filters jobs by queue', function (): void {
    ConductorJob::factory()->count(2)->create(['queue' => 'high']);
    ConductorJob::factory()->count(3)->create(['queue' => 'default']);

    $response = $this->getJson('/conductor/api/jobs?queue=high')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('filters jobs by tag', function (): void {
    ConductorJob::factory()->create(['tags' => ['billing', 'email']]);
    ConductorJob::factory()->create(['tags' => ['email']]);
    ConductorJob::factory()->create(['tags' => []]);

    $response = $this->getJson('/conductor/api/jobs?tag=billing')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('returns job detail with logs', function (): void {
    $job = ConductorJob::factory()->create(['stack_trace' => 'visible stack trace']);
    ConductorJobLog::factory()->count(3)->create(['job_id' => $job->id]);

    $response = $this->getJson("/conductor/api/jobs/{$job->uuid}")
        ->assertOk();

    expect($response->json('data.logs'))->toHaveCount(3)
        ->and($response->json('data.id'))->toBe($job->uuid)
        ->and($response->json('data.stack_trace'))->toBe('visible stack trace');
});

it('retries a failed job', function (): void {
    $originalJob = new JobApiTrackableJob();
    $encrypted = Crypt::encryptString(serialize($originalJob));

    $job = ConductorJob::factory()->failed()->create([
        'class' => JobApiTrackableJob::class,
        'payload' => ['display' => [], 'retry' => $encrypted],
        'queue' => 'default',
        'connection' => 'sync',
        'attempts' => 1,
    ]);

    $this->postJson("/conductor/api/jobs/{$job->uuid}/retry")
        ->assertOk()
        ->assertJson(['message' => 'Job retry dispatched.']);

    $job->refresh();
    expect($job->status)->toBe(JobStatus::Completed);
});

it('rejects retry on non-failed job', function (): void {
    $job = ConductorJob::factory()->completed()->create();

    $this->postJson("/conductor/api/jobs/{$job->uuid}/retry")
        ->assertStatus(422);
});

it('cancels a pending job', function (): void {
    $job = ConductorJob::factory()->create(['status' => JobStatus::Pending]);

    $this->deleteJson("/conductor/api/jobs/{$job->uuid}")
        ->assertOk()
        ->assertJson(['message' => 'Job cancellation requested.']);

    $job->refresh();
    expect($job->status)->toBe(JobStatus::Cancelled);
});

it('rejects cancellation of terminal job', function (): void {
    $job = ConductorJob::factory()->completed()->create();

    $this->deleteJson("/conductor/api/jobs/{$job->uuid}")
        ->assertStatus(422);
});

it('blocks unauthenticated access', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');
    $this->setConductorAuth();

    $this->getJson('/conductor/api/jobs')
        ->assertForbidden();
});
