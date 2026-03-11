<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WebhookLogStatus;
use HotReloadStudios\Conductor\Jobs\WebhookFunctionJob;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\Models\ConductorWebhookSource;
use HotReloadStudios\Conductor\WebhookFunction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

final class StripeWebhookFunction extends WebhookFunction
{
    public function handle(array $payload, string $source): void {}
}

function makeSignedRequest(string $body, string $secret, string $source = 'stripe'): Illuminate\Testing\TestResponse
{
    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    return test()->call(
        'POST',
        '/conductor/webhook/'.$source,
        [],
        [],
        [],
        ['HTTP_X_HUB_SIGNATURE_256' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $body,
    );
}

beforeEach(function (): void {
    RateLimiter::clear('conductor-webhook:stripe:127.0.0.1');
    Queue::fake();
});

it('processes a webhook with valid signature', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', null);

    $body = '{"event":"charge.succeeded"}';
    makeSignedRequest($body, 'my-secret')->assertSuccessful();

    expect(ConductorWebhookLog::where('status', WebhookLogStatus::Processed)->count())->toBe(1);
});

it('queues a webhook function job after successful verification', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', null);

    $body = '{"event":"charge.succeeded","amount":100}';
    makeSignedRequest($body, 'my-secret');

    Queue::assertPushed(WebhookFunctionJob::class, function (WebhookFunctionJob $job): bool {
        return $job->source === 'stripe'
            && $job->functionClass === StripeWebhookFunction::class
            && $job->payload['event'] === 'charge.succeeded';
    });
});

it('rejects a webhook with invalid signature', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', null);

    $body = '{"event":"charge.succeeded"}';
    $signature = 'sha256=badsignature';

    test()->call(
        'POST',
        '/conductor/webhook/stripe',
        [],
        [],
        [],
        ['HTTP_X_HUB_SIGNATURE_256' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $body,
    )->assertForbidden();

    expect(ConductorWebhookLog::where('status', WebhookLogStatus::Failed)->count())->toBe(1);
});

it('returns 404 for unconfigured webhook sources', function (): void {
    config()->set('conductor.webhooks', []);
    config()->set('conductor.webhook_rate_limit', null);

    test()->call('POST', '/conductor/webhook/unknown', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], '{}')->assertNotFound();
});

it('rate limits webhook requests per source ip', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
        'github' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', 2);

    $body = '{"event":"test"}';
    $signature = 'sha256='.hash_hmac('sha256', $body, 'my-secret');

    $baseServer = ['HTTP_X_HUB_SIGNATURE_256' => $signature, 'CONTENT_TYPE' => 'application/json'];

    test()->call('POST', '/conductor/webhook/stripe', [], [], [], $baseServer, $body)->assertSuccessful();
    test()->call('POST', '/conductor/webhook/stripe', [], [], [], $baseServer, $body)->assertSuccessful();
    test()->call('POST', '/conductor/webhook/stripe', [], [], [], $baseServer, $body)->assertTooManyRequests();

    RateLimiter::clear('conductor-webhook:github:127.0.0.1');
    test()->call('POST', '/conductor/webhook/github', [], [], [], $baseServer, $body)->assertSuccessful();
});

it('masks the signature in webhook logs', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', null);

    $body = '{"event":"test"}';
    $longSignature = 'sha256='.hash_hmac('sha256', $body, 'my-secret');

    test()->call(
        'POST',
        '/conductor/webhook/stripe',
        [],
        [],
        [],
        ['HTTP_X_HUB_SIGNATURE_256' => $longSignature, 'CONTENT_TYPE' => 'application/json'],
        $body,
    );

    $log = ConductorWebhookLog::first();
    expect($log->masked_signature)->toEndWith('****')
        ->and(str_contains($log->masked_signature, '****'))->toBeTrue()
        ->and(mb_strlen($log->masked_signature))->toBeLessThan(mb_strlen($longSignature));
});

it('redacts sensitive keys in webhook payload', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', null);
    config()->set('conductor.redact_keys', ['token']);

    $body = json_encode(['token' => 'secret-token', 'event' => 'charge.succeeded']);
    makeSignedRequest((string) $body, 'my-secret');

    $log = ConductorWebhookLog::first();
    expect($log->payload['token'])->toBe('[REDACTED]')
        ->and($log->payload['event'])->toBe('charge.succeeded');
});

it('handles inactive webhook sources', function (): void {
    config()->set('conductor.webhooks', [
        'stripe' => ['secret' => 'my-secret', 'function' => StripeWebhookFunction::class],
    ]);
    config()->set('conductor.webhook_rate_limit', null);

    ConductorWebhookSource::factory()->create(['source' => 'stripe', 'is_active' => false]);

    $body = '{"event":"test"}';
    $signature = 'sha256='.hash_hmac('sha256', $body, 'my-secret');

    $response = test()->call(
        'POST',
        '/conductor/webhook/stripe',
        [],
        [],
        [],
        ['HTTP_X_HUB_SIGNATURE_256' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $body,
    );

    $response->assertSuccessful();
    $responseData = $response->json();
    expect($responseData['status'])->toBe('inactive');
    Queue::assertNothingPushed();
});
