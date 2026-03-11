<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class WebhookRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get(self::class) === true) {
            return $next($request);
        }

        $request->attributes->set(self::class, true);

        $limit = config('conductor.webhook_rate_limit');

        if ($limit === null) {
            return $next($request);
        }

        $key = 'conductor-webhook:'.$request->route('source').':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, (int) $limit)) {
            return response('Too Many Requests', 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
