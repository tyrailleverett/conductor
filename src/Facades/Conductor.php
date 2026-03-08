<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HotReloadStudios\Conductor\Conductor
 *
 * @method static void auth(\Closure $callback)
 */
final class Conductor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HotReloadStudios\Conductor\Conductor::class;
    }
}
