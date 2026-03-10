<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class DashboardController
{
    public function __invoke(Request $request): Response
    {
        return response()->view('conductor::index');
    }
}
