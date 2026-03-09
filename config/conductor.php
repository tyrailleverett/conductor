<?php

declare(strict_types=1);

$webhookRateLimit = env('CONDUCTOR_WEBHOOK_RATE_LIMIT', 60);

return [
    'path' => env('CONDUCTOR_PATH', 'conductor'),

    'middleware' => ['web'],

    'queue' => [
        'connection' => env('CONDUCTOR_QUEUE_CONNECTION'),
        'queue' => env('CONDUCTOR_QUEUE', 'conductor'),
    ],

    'prune_after_days' => (int) env('CONDUCTOR_PRUNE_AFTER_DAYS', 7),

    'heartbeat_interval' => (int) env('CONDUCTOR_HEARTBEAT_INTERVAL', 15),

    'worker_timeout' => (int) env('CONDUCTOR_WORKER_TIMEOUT', 60),

    'redact_keys' => [
        'password',
        'token',
        'authorization',
        'secret',
        'api_key',
        'cookie',
        'x-signature',
        'x-hub-signature',
    ],

    'functions' => [],

    /*
    'webhooks' => [
        'stripe' => [
            'secret' => env('CONDUCTOR_WEBHOOK_STRIPE_SECRET'),
            'function' => 'billing.handle-stripe-webhook',
        ],
    ],
    */
    'webhooks' => [],

    'webhook_rate_limit' => $webhookRateLimit === null ? null : (int) $webhookRateLimit,
];
