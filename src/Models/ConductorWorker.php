<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorWorkerFactory;
use HotReloadStudios\Conductor\Enums\WorkerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConductorWorker extends Model
{
    /** @use HasFactory<ConductorWorkerFactory> */
    use HasFactory;

    protected $table = 'conductor_workers';

    protected $fillable = [
        'worker_uuid',
        'worker_name',
        'queue',
        'connection',
        'hostname',
        'process_id',
        'current_job_uuid',
        'last_heartbeat_at',
    ];

    public function derivedStatus(): WorkerStatus
    {
        if ($this->current_job_uuid !== null) {
            return WorkerStatus::Busy;
        }

        if ($this->last_heartbeat_at->isBefore(now()->subSeconds((int) config('conductor.worker_timeout')))) {
            return WorkerStatus::Offline;
        }

        return WorkerStatus::Idle;
    }

    public function getRouteKeyName(): string
    {
        return 'worker_uuid';
    }

    protected static function newFactory(): ConductorWorkerFactory
    {
        return ConductorWorkerFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_heartbeat_at' => 'datetime',
        ];
    }
}
