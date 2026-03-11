<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('returns a paginated list of workflows', function (): void {
    ConductorWorkflow::factory()->count(5)->create();

    $this->getJson('/conductor/api/workflows')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('returns workflow detail with steps', function (): void {
    $workflow = ConductorWorkflow::factory()->create();
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 0]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 1]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 2]);

    $response = $this->getJson("/conductor/api/workflows/{$workflow->uuid}")
        ->assertOk();

    expect($response->json('data.steps'))->toHaveCount(3)
        ->and($response->json('data.id'))->toBe($workflow->uuid);
});

it('rejects an invalid status filter', function (): void {
    $this->getJson('/conductor/api/workflows?status=not-a-status')
        ->assertStatus(422)
        ->assertJson(['message' => 'The selected status is invalid.']);
});

it('cancels a running workflow', function (): void {
    $workflow = ConductorWorkflow::factory()->running()->create();
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 0]);

    $this->deleteJson("/conductor/api/workflows/{$workflow->uuid}")
        ->assertOk()
        ->assertJson(['message' => 'Workflow cancellation requested.']);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Cancelled);
});
