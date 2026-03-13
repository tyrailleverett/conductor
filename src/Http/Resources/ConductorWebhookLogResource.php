<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorWebhookLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'payload' => $this->payload,
            'masked_signature' => $this->masked_signature,
            'status' => $this->status->value,
            'received_at' => $this->received_at?->toIso8601String(),
        ];
    }
}
