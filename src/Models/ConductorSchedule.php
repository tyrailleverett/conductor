<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorScheduleFactory;
use HotReloadStudios\Conductor\Enums\ScheduleRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConductorSchedule extends Model
{
    /** @use HasFactory<ConductorScheduleFactory> */
    use HasFactory;

    protected $table = 'conductor_schedules';

    protected $fillable = [
        'function_class',
        'display_name',
        'cron_expression',
        'is_active',
        'last_run_at',
        'next_run_at',
        'last_run_status',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected static function newFactory(): ConductorScheduleFactory
    {
        return ConductorScheduleFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_run_status' => ScheduleRunStatus::class,
        ];
    }
}
