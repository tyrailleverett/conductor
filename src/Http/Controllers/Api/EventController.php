<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Http\Resources\ConductorEventResource;
use HotReloadStudios\Conductor\Models\ConductorEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class EventController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ConductorEvent::query()
            ->withCount('runs')
            ->orderBy('dispatched_at', 'desc');

        if ($request->has('name')) {
            $query->where('name', $request->query('name'));
        }

        $perPage = min((int) ($request->query('per_page', '15')), 100);

        return ConductorEventResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, ConductorEvent $event): ConductorEventResource
    {
        $event->load('runs');

        return new ConductorEventResource($event);
    }
}
