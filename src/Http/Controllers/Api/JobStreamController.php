<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Http\Resources\ConductorJobLogResource;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class JobStreamController
{
    public function __invoke(Request $request, ConductorJob $job): StreamedResponse
    {
        return response()->stream(function () use ($request, $job): void {
            $lastEventId = $request->header('Last-Event-ID');
            $lastSeen = now()->subSeconds(1);

            if ($lastEventId !== null) {
                try {
                    $lastSeen = Carbon::parse($lastEventId);
                } catch (Throwable) {
                    $lastSeen = now()->subSeconds(1);
                }
            }

            while (true) {
                $logs = ConductorJobLog::where('job_id', $job->id)
                    ->where('logged_at', '>', $lastSeen)
                    ->orderBy('logged_at')
                    ->get();

                foreach ($logs as $log) {
                    $resource = new ConductorJobLogResource($log);
                    echo 'data: '.json_encode($resource->toArray($request))."\n\n";
                    $lastSeen = $log->logged_at;
                }

                $job->refresh();

                if ($job->status->isTerminal()) {
                    echo 'data: {"event":"done"}'."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();
                    break;
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();

                if (connection_aborted()) {
                    break;
                }

                usleep(500_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
