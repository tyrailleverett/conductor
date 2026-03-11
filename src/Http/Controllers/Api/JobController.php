<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Http\Resources\ConductorJobResource;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Services\JobCancellationService;
use HotReloadStudios\Conductor\Services\JobRetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;
use LogicException;

final class JobController
{
    public function __construct(
        private readonly JobRetryService $jobRetryService,
        private readonly JobCancellationService $jobCancellationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = ConductorJob::query()
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $status = JobStatus::tryFrom((string) $request->query('status'));

            if ($status === null) {
                return response()->json(['message' => 'The selected status is invalid.'], 422);
            }

            $query->withStatus($status);
        }

        if ($request->has('queue')) {
            $query->onQueue($request->query('queue'));
        }

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->query('tag'));
        }

        $perPage = min((int) ($request->query('per_page', '15')), 100);

        return ConductorJobResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, ConductorJob $job): ConductorJobResource
    {
        $job->load(['logs' => fn ($q) => $q->orderBy('logged_at')]);

        return new ConductorJobResource($job);
    }

    public function retry(ConductorJob $job): JsonResponse
    {
        try {
            $this->jobRetryService->retry($job);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Job retry dispatched.']);
    }

    public function destroy(ConductorJob $job): JsonResponse
    {
        try {
            $this->jobCancellationService->cancel($job);
        } catch (LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Job cancellation requested.']);
    }
}
