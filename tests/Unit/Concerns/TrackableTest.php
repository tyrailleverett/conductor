<?php

// phpcs:ignoreFile

declare(strict_types=1);

use HotReloadStudios\Conductor\Concerns\Trackable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class TrackableTestBasicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public function handle(): void {}
}

it('provides a default display name from class basename', function (): void {
    $job = new TrackableTestBasicJob();

    expect($job->displayName())->toBe('TrackableTestBasicJob');
});

it('returns empty tags by default', function (): void {
    $job = new TrackableTestBasicJob();

    expect($job->conductorTags())->toBe([]);
});
