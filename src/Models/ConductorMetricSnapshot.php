<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Models;

use HotReloadStudios\Conductor\Database\Factories\ConductorMetricSnapshotFactory;
use HotReloadStudios\Conductor\Enums\MetricType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConductorMetricSnapshot extends Model
{
    /** @use HasFactory<ConductorMetricSnapshotFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conductor_metric_snapshots';

    protected $fillable = [
        'metric',
        'queue',
        'value',
        'recorded_at',
    ];

    protected static function newFactory(): ConductorMetricSnapshotFactory
    {
        return ConductorMetricSnapshotFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metric' => MetricType::class,
            'value' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }
}
