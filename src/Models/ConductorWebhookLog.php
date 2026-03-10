<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorWebhookLogFactory;
use HotReloadStudios\Conductor\Enums\WebhookLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConductorWebhookLog extends Model
{
    /** @use HasFactory<ConductorWebhookLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conductor_webhook_logs';

    protected $fillable = [
        'source',
        'payload',
        'masked_signature',
        'status',
        'received_at',
    ];

    public function webhookSource(): BelongsTo
    {
        return $this->belongsTo(ConductorWebhookSource::class, 'source', 'source');
    }

    protected static function newFactory(): ConductorWebhookLogFactory
    {
        return ConductorWebhookLogFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookLogStatus::class,
            'received_at' => 'datetime',
        ];
    }
}
