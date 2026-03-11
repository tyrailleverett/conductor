<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('returns an SSE response with correct headers', function (): void {
    $job = ConductorJob::factory()->completed()->create();

    $response = $this->get("/conductor/api/jobs/{$job->uuid}/stream");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
});

it('streams log entries as SSE data', function (): void {
    $job = ConductorJob::factory()->completed()->create();
    ConductorJobLog::factory()->create([
        'job_id' => $job->id,
        'message' => 'Processing started',
        'logged_at' => now(),
    ]);

    $response = $this->get("/conductor/api/jobs/{$job->uuid}/stream");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

    $streamedContent = $response->streamedContent();

    expect($streamedContent)
        ->toContain('Processing started')
        ->toContain('data: {"event":"done"}');
});

it('ignores an invalid last event id header', function (): void {
    $job = ConductorJob::factory()->completed()->create();

    $response = $this->withHeader('Last-Event-ID', 'not-a-timestamp')
        ->get("/conductor/api/jobs/{$job->uuid}/stream");

    $response->assertOk();
    expect($response->streamedContent())->toContain('data: {"event":"done"}');
});
