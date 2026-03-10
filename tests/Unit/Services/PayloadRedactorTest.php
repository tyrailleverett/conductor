<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Services\PayloadRedactor;

it('redacts values for configured keys', function (): void {
    config()->set('conductor.redact_keys', ['password', 'token']);

    $redactor = new PayloadRedactor();

    $result = $redactor->redact([
        'username' => 'alice',
        'password' => 'hunter2',
        'token' => 'abc123',
    ]);

    expect($result['username'])->toBe('alice')
        ->and($result['password'])->toBe('[REDACTED]')
        ->and($result['token'])->toBe('[REDACTED]');
});

it('redacts nested keys recursively', function (): void {
    config()->set('conductor.redact_keys', ['secret']);

    $redactor = new PayloadRedactor();

    $result = $redactor->redact([
        'outer' => [
            'inner' => [
                'secret' => 'top-secret-value',
            ],
        ],
    ]);

    expect($result['outer']['inner']['secret'])->toBe('[REDACTED]');
});

it('preserves non-sensitive keys', function (): void {
    config()->set('conductor.redact_keys', ['password']);

    $redactor = new PayloadRedactor();

    $result = $redactor->redact([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ]);

    expect($result['name'])->toBe('Alice')
        ->and($result['email'])->toBe('alice@example.com');
});

it('is case-insensitive for key matching', function (): void {
    config()->set('conductor.redact_keys', ['password', 'token']);

    $redactor = new PayloadRedactor();

    $result = $redactor->redact([
        'PASSWORD' => 'secret',
        'Token' => 'abc',
    ]);

    expect($result['PASSWORD'])->toBe('[REDACTED]')
        ->and($result['Token'])->toBe('[REDACTED]');
});

it('handles empty arrays', function (): void {
    config()->set('conductor.redact_keys', ['password']);

    $redactor = new PayloadRedactor();

    $result = $redactor->redact([]);

    expect($result)->toBe([]);
});
