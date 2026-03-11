<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs vendor:publish with conductor-assets tag', function (): void {
    $this->artisan('conductor:publish')
        ->expectsOutputToContain('Conductor assets published successfully.')
        ->assertExitCode(0);
});

it('accepts the force option', function (): void {
    $this->artisan('conductor:publish', ['--force' => true])
        ->expectsOutputToContain('Conductor assets published successfully.')
        ->assertExitCode(0);
});
