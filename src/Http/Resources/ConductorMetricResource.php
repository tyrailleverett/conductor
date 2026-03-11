<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorMetricResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'metric' => $this->metric->value,
            'queue' => $this->queue,
            'value' => $this->value,
            'recorded_at' => $this->recorded_at->toIso8601String(),
        ];
    }
}
