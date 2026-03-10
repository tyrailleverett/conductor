<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Logging;

use HotReloadStudios\Conductor\Enums\LogLevel;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use HotReloadStudios\Conductor\Services\PayloadRedactor;
use HotReloadStudios\Conductor\Support\ConductorContext;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

final class ConductorLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (! ConductorContext::isActive()) {
            return;
        }

        $jobId = ConductorContext::get();

        $logLevel = match (true) {
            $record->level === Level::Debug => LogLevel::Debug,
            $record->level === Level::Info => LogLevel::Info,
            $record->level === Level::Warning => LogLevel::Warning,
            default => LogLevel::Error,
        };

        $message = $record->message;

        if ($record->context !== []) {
            $redactor = new PayloadRedactor();
            $redactedContext = $redactor->redact($record->context);
            $message .= ' '.json_encode($redactedContext);
        }

        ConductorJobLog::create([
            'job_id' => $jobId,
            'level' => $logLevel,
            'message' => $message,
            'logged_at' => $record->datetime,
        ]);
    }
}
