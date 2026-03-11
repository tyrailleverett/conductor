<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\ScheduleRunStatus;
use HotReloadStudios\Conductor\Models\ConductorSchedule;
use HotReloadStudios\Conductor\ScheduledFunction;
use HotReloadStudios\Conductor\Services\ScheduleRegistrar;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

final class DailyReportFunction extends ScheduledFunction
{
    public function schedule(): string
    {
        return '0 9 * * *';
    }

    public function handle(): void {}
}

final class HourlyPingFunction extends ScheduledFunction
{
    public function schedule(): string
    {
        return 'hourly';
    }

    public function handle(): void {}
}

final class FailingScheduledFunction extends ScheduledFunction
{
    public function schedule(): string
    {
        return 'everyMinute';
    }

    public function handle(): void
    {
        throw new RuntimeException('Schedule failed intentionally.');
    }
}

it('registers scheduled functions into laravel scheduler', function (): void {
    config()->set('conductor.functions', [DailyReportFunction::class]);

    $schedule = app(Schedule::class);
    $registrar = new ScheduleRegistrar();
    $registrar->register($schedule);

    $events = $schedule->events();

    expect(count($events))->toBeGreaterThan(0);
});

it('creates conductor_schedules records on registration', function (): void {
    config()->set('conductor.functions', [DailyReportFunction::class]);

    $schedule = app(Schedule::class);
    $registrar = new ScheduleRegistrar();
    $registrar->register($schedule);

    expect(ConductorSchedule::where('function_class', DailyReportFunction::class)->exists())->toBeTrue();

    $record = ConductorSchedule::where('function_class', DailyReportFunction::class)->first();
    expect($record->cron_expression)->toBe('0 9 * * *')
        ->and($record->display_name)->toBe('DailyReportFunction');
});

it('skips execution when schedule is inactive', function (): void {
    ConductorSchedule::factory()->create([
        'function_class' => DailyReportFunction::class,
        'is_active' => false,
        'last_run_at' => null,
    ]);

    config()->set('conductor.functions', [DailyReportFunction::class]);

    $schedule = app(Schedule::class);
    $registrar = new ScheduleRegistrar();
    $registrar->register($schedule);

    foreach ($schedule->events() as $event) {
        $event->run(app());
    }

    $record = ConductorSchedule::where('function_class', DailyReportFunction::class)->first();
    expect($record->last_run_at)->toBeNull();
});

it('updates last_run_at and last_run_status on successful execution', function (): void {
    config()->set('conductor.functions', [DailyReportFunction::class]);

    $schedule = app(Schedule::class);
    $registrar = new ScheduleRegistrar();
    $registrar->register($schedule);

    foreach ($schedule->events() as $event) {
        $event->run(app());
    }

    $record = ConductorSchedule::where('function_class', DailyReportFunction::class)->first();
    expect($record->last_run_at)->not->toBeNull()
        ->and($record->last_run_status)->toBe(ScheduleRunStatus::Completed);
});
