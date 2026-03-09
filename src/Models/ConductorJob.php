<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorJobFactory;
use HotReloadStudios\Conductor\Enums\JobStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ConductorJob extends Model
{
    /** @use HasFactory<ConductorJobFactory> */
    use HasFactory;

    protected $table = 'conductor_jobs';

    protected $fillable = [
        'uuid',
        'class',
        'display_name',
        'status',
        'queue',
        'connection',
        'tags',
        'payload',
        'attempts',
        'max_attempts',
        'cancellable_at',
        'cancellation_requested_at',
        'cancelled_at',
        'started_at',
        'completed_at',
        'failed_at',
        'duration_ms',
        'error_message',
        'stack_trace',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(ConductorJobLog::class, 'job_id');
    }

    public function workflowStep(): HasOne
    {
        return $this->hasOne(ConductorWorkflowStep::class, 'conductor_job_id');
    }

    public function eventRun(): HasOne
    {
        return $this->hasOne(ConductorEventRun::class, 'conductor_job_id');
    }

    public function scopeWithStatus(Builder $query, JobStatus $status): void
    {
        $query->where('status', $status);
    }

    public function scopeOnQueue(Builder $query, string $queue): void
    {
        $query->where('queue', $queue);
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', JobStatus::Failed);
    }

    public function scopeWithTag(Builder $query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }

    public function isCancellable(): bool
    {
        return $this->status === JobStatus::Pending
            || ($this->status === JobStatus::Running && $this->cancellable_at !== null);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function newFactory(): ConductorJobFactory
    {
        return ConductorJobFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'tags' => 'array',
            'payload' => 'array',
            'cancellable_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
