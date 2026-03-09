<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorWorkflowStepFactory;
use HotReloadStudios\Conductor\Enums\StepStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConductorWorkflowStep extends Model
{
    /** @use HasFactory<ConductorWorkflowStepFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conductor_workflow_steps';

    protected $fillable = [
        'workflow_id',
        'conductor_job_id',
        'name',
        'step_index',
        'status',
        'input',
        'output',
        'available_at',
        'attempts',
        'error_message',
        'stack_trace',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ConductorWorkflow::class, 'workflow_id');
    }

    public function conductorJob(): BelongsTo
    {
        return $this->belongsTo(ConductorJob::class, 'conductor_job_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    protected static function newFactory(): ConductorWorkflowStepFactory
    {
        return ConductorWorkflowStepFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StepStatus::class,
            'input' => 'array',
            'output' => 'array',
            'available_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
