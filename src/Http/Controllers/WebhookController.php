<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers;

use HotReloadStudios\Conductor\Enums\WebhookLogStatus;
use HotReloadStudios\Conductor\Jobs\WebhookFunctionJob;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\Models\ConductorWebhookSource;
use HotReloadStudios\Conductor\Services\PayloadRedactor;
use HotReloadStudios\Conductor\Services\WebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookController
{
    public function __construct(
        private readonly WebhookVerifier $verifier,
        private readonly PayloadRedactor $payloadRedactor,
    ) {}

    public function __invoke(Request $request, string $source): JsonResponse
    {
        /** @var array{secret: string, function: string}|null $config */
        $config = config("conductor.webhooks.{$source}");

        if ($config === null) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        /** @var ConductorWebhookSource $webhookSource */
        $webhookSource = ConductorWebhookSource::firstOrNew(['source' => $source]);
        $webhookSource->function_class = $config['function'];

        if (! $webhookSource->exists) {
            $webhookSource->is_active = true;
        }

        $webhookSource->save();

        if (! $webhookSource->is_active) {
            return response()->json(['status' => 'inactive']);
        }

        $normalizedPayload = $this->normalizePayload($request);
        $redactedPayload = $this->payloadRedactor->redact($normalizedPayload);
        $rawSignature = $this->resolveSignatureHeader($request);
        $maskedSignature = $this->maskSignature($rawSignature);

        $webhookLog = ConductorWebhookLog::create([
            'source' => $source,
            'payload' => $redactedPayload,
            'masked_signature' => $maskedSignature,
            'status' => WebhookLogStatus::Received,
            'received_at' => now(),
        ]);

        if (! $this->verifier->verify($request, $config['secret'])) {
            $webhookLog->update(['status' => WebhookLogStatus::Failed]);

            return response()->json(['error' => 'Forbidden'], 403);
        }

        $webhookLog->update(['status' => WebhookLogStatus::Verified]);

        WebhookFunctionJob::dispatch($webhookLog->id, $source, $config['function'], $normalizedPayload);

        $webhookLog->update(['status' => WebhookLogStatus::Processed]);

        return response()->json(['status' => 'processed']);
    }

    /**
     * @return array<mixed>
     */
    private function normalizePayload(Request $request): array
    {
        if ($request->isJson()) {
            return $request->json()->all();
        }

        $formData = $request->request->all();

        if (! empty($formData)) {
            return $formData;
        }

        $content = $request->getContent();

        return $content !== '' ? ['raw_body' => $content] : [];
    }

    private function resolveSignatureHeader(Request $request): ?string
    {
        return $request->header('X-Signature')
            ?? $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Signature-256');
    }

    private function maskSignature(?string $signature): ?string
    {
        if ($signature === null || $signature === '') {
            return null;
        }

        return mb_substr($signature, 0, 8).'****';
    }
}
