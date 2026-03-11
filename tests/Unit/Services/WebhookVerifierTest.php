<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Services\WebhookVerifier;
use Illuminate\Http\Request;

it('verifies a valid sha256 HMAC signature', function (): void {
    $secret = 'my-secret';
    $body = '{"event":"test"}';
    $signature = hash_hmac('sha256', $body, $secret);

    $request = Request::create('/webhook/test', 'POST', [], [], [], [], $body);
    $request->headers->set('X-Signature', $signature);

    $verifier = new WebhookVerifier();

    expect($verifier->verify($request, $secret))->toBeTrue();
});

it('rejects an invalid signature', function (): void {
    $secret = 'my-secret';
    $body = '{"event":"test"}';

    $request = Request::create('/webhook/test', 'POST', [], [], [], [], $body);
    $request->headers->set('X-Signature', 'wrong-signature');

    $verifier = new WebhookVerifier();

    expect($verifier->verify($request, $secret))->toBeFalse();
});

it('handles sha256= prefixed signatures', function (): void {
    $secret = 'my-secret';
    $body = '{"event":"test"}';
    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    $request = Request::create('/webhook/test', 'POST', [], [], [], [], $body);
    $request->headers->set('X-Signature', $signature);

    $verifier = new WebhookVerifier();

    expect($verifier->verify($request, $secret))->toBeTrue();
});

it('checks multiple header names', function (): void {
    $secret = 'my-secret';
    $body = '{"event":"test"}';
    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    $request = Request::create('/webhook/test', 'POST', [], [], [], [], $body);
    $request->headers->set('X-Hub-Signature-256', $signature);

    $verifier = new WebhookVerifier();

    expect($verifier->verify($request, $secret))->toBeTrue();
});

it('returns false when no signature header is present', function (): void {
    $request = Request::create('/webhook/test', 'POST', [], [], [], [], '{"event":"test"}');

    $verifier = new WebhookVerifier();

    expect($verifier->verify($request, 'secret'))->toBeFalse();
});
