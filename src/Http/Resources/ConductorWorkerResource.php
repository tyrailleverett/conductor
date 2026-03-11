<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorWorkerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->worker_uuid,
            'worker_name' => $this->worker_name,
            'queue' => $this->queue,
            'connection' => $this->connection,
            'hostname' => $this->hostname,
            'process_id' => $this->process_id,
            'status' => $this->derivedStatus()->value,
            'current_job_uuid' => $this->current_job_uuid,
            'last_heartbeat_at' => $this->last_heartbeat_at->toIso8601String(),
        ];
    }
}
