<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorJobLogFactory;
use HotReloadStudios\Conductor\Enums\LogLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConductorJobLog extends Model
{
    /** @use HasFactory<ConductorJobLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conductor_job_logs';

    protected $fillable = [
        'job_id',
        'level',
        'message',
        'logged_at',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ConductorJob::class, 'job_id');
    }

    protected static function newFactory(): ConductorJobLogFactory
    {
        return ConductorJobLogFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'level' => LogLevel::class,
            'logged_at' => 'datetime',
        ];
    }
}
