<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorEventRunFactory;
use HotReloadStudios\Conductor\Enums\EventRunStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConductorEventRun extends Model
{
    /** @use HasFactory<ConductorEventRunFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conductor_event_runs';

    protected $fillable = [
        'event_id',
        'conductor_job_id',
        'function_class',
        'status',
        'error_message',
        'attempts',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(ConductorEvent::class, 'event_id');
    }

    public function conductorJob(): BelongsTo
    {
        return $this->belongsTo(ConductorJob::class, 'conductor_job_id');
    }

    protected static function newFactory(): ConductorEventRunFactory
    {
        return ConductorEventRunFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => EventRunStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
