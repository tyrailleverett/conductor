<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Http\Resources\ConductorWorkflowResource;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Services\WorkflowCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

final class WorkflowController
{
    public function __construct(
        private readonly WorkflowCancellationService $workflowCancellationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = ConductorWorkflow::query()
            ->withCount('steps')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $status = WorkflowStatus::tryFrom((string) $request->query('status'));

            if ($status === null) {
                return response()->json(['message' => 'The selected status is invalid.'], 422);
            }

            $query->withStatus($status);
        }

        $perPage = min((int) ($request->query('per_page', '15')), 100);

        return ConductorWorkflowResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, ConductorWorkflow $workflow): ConductorWorkflowResource
    {
        $workflow->load(['steps' => fn ($q) => $q->orderBy('step_index')]);

        return new ConductorWorkflowResource($workflow);
    }

    public function destroy(ConductorWorkflow $workflow): JsonResponse
    {
        try {
            $this->workflowCancellationService->cancel($workflow);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Workflow cancellation requested.']);
    }
}
