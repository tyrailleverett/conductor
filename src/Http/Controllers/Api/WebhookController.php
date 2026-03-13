<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Http\Resources\ConductorWebhookLogResource;
use HotReloadStudios\Conductor\Http\Resources\ConductorWebhookSourceResource;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\Models\ConductorWebhookSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookController
{
    public function index(Request $request): JsonResponse
    {
        $sources = ConductorWebhookSource::query()
            ->withCount('logs')
            ->orderBy('source')
            ->get();

        $logsQuery = ConductorWebhookLog::query()
            ->orderByDesc('received_at');

        if ($request->filled('source')) {
            $logsQuery->where('source', $request->string('source')->toString());
        }

        $logs = $logsQuery
            ->limit(min((int) $request->query('limit', '25'), 100))
            ->get();

        return response()->json([
            'data' => [
                'sources' => ConductorWebhookSourceResource::collection($sources),
                'logs' => ConductorWebhookLogResource::collection($logs),
            ],
        ]);
    }
}
