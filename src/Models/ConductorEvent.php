<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorEventFactory;
use HotReloadStudios\Conductor\Services\EventDispatchService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ConductorEvent extends Model
{
    /** @use HasFactory<ConductorEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conductor_events';

    protected $fillable = [
        'uuid',
        'name',
        'payload',
        'dispatched_at',
    ];

    /**
     * Dispatch a named event through Conductor's event bus.
     *
     * @param  array<mixed>  $payload
     */
    public static function dispatch(string $name, array $payload = []): self
    {
        return app(EventDispatchService::class)->dispatch($name, $payload);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ConductorEventRun::class, 'event_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function newFactory(): ConductorEventFactory
    {
        return ConductorEventFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'dispatched_at' => 'datetime',
        ];
    }
}
