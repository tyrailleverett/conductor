<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Http\Resources\ConductorWorkerResource;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;

final class WorkerController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        if (Config::get('queue.default') === 'sync') {
            return response()->json([
                'sync_driver' => true,
                'message' => 'Worker health is not available with the sync queue driver.',
                'data' => [],
            ]);
        }

        $workers = ConductorWorker::query()
            ->orderBy('queue')
            ->orderBy('worker_name')
            ->get();

        return ConductorWorkerResource::collection($workers);
    }
}
