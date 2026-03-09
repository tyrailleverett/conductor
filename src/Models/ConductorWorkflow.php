<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorWorkflowFactory;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ConductorWorkflow extends Model
{
    /** @use HasFactory<ConductorWorkflowFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'conductor_workflows';

    protected $fillable = [
        'uuid',
        'class',
        'display_name',
        'status',
        'input',
        'output',
        'current_step_index',
        'next_run_at',
        'sleep_until',
        'waiting_for_event',
        'completed_at',
        'cancelled_at',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ConductorWorkflowStep::class, 'workflow_id')->orderBy('step_index');
    }

    public function currentStep(): HasOne
    {
        return $this->hasOne(ConductorWorkflowStep::class, 'workflow_id')
            ->ofMany([], function (Builder $query): void {
                $query->where('step_index', $this->current_step_index);
            });
    }

    public function scopeWithStatus(Builder $query, WorkflowStatus $status): void
    {
        $query->where('status', $status);
    }

    public function scopeRunnable(Builder $query): void
    {
        $query->where('status', WorkflowStatus::Running)
            ->where(function (Builder $q): void {
                $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            });
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function newFactory(): ConductorWorkflowFactory
    {
        return ConductorWorkflowFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => WorkflowStatus::class,
            'input' => 'array',
            'output' => 'array',
            'next_run_at' => 'datetime',
            'sleep_until' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
