<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'payload' => $this->payload,
            'dispatched_at' => $this->dispatched_at->toIso8601String(),
            'runs_count' => $this->when(isset($this->runs_count), $this->runs_count),
            'runs' => ConductorEventRunResource::collection($this->whenLoaded('runs')),
        ];
    }
}
