<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

use Closure;
use Illuminate\Http\Request;

final class Conductor
{
    private static ?Closure $authUsing = null;

    public static function auth(Closure $callback): void
    {
        self::$authUsing = $callback;
    }

    public static function check(Request $request): bool
    {
        if (self::$authUsing instanceof Closure) {
            return (bool) call_user_func(self::$authUsing, $request);
        }

        return app()->environment('local');
    }
}
