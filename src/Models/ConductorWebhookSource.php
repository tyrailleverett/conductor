<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorWebhookSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ConductorWebhookSource extends Model
{
    /** @use HasFactory<ConductorWebhookSourceFactory> */
    use HasFactory;

    protected $table = 'conductor_webhook_sources';

    protected $fillable = [
        'source',
        'function_class',
        'is_active',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(ConductorWebhookLog::class, 'source', 'source');
    }

    public function getRouteKeyName(): string
    {
        return 'source';
    }

    protected static function newFactory(): ConductorWebhookSourceFactory
    {
        return ConductorWebhookSourceFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
