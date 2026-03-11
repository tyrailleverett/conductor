<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use Illuminate\Http\Request;

final class WebhookVerifier
{
    public function verify(Request $request, string $secret): bool
    {
        $rawSignature = $request->header('X-Signature')
            ?? $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Signature-256');

        if ($rawSignature === null || $rawSignature === '') {
            return false;
        }

        $algorithm = 'sha256';
        $signature = $rawSignature;

        if (str_contains($rawSignature, '=')) {
            [$algorithm, $signature] = explode('=', $rawSignature, 2);
        }

        $expected = hash_hmac($algorithm, $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
