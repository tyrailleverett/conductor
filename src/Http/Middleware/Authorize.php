<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Middleware;

use Closure;
use HotReloadStudios\Conductor\Conductor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Conductor::check($request), 403);

        return $next($request);
    }
}
