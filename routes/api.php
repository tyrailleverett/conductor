<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Http\Controllers\Api\EventController;
use HotReloadStudios\Conductor\Http\Controllers\Api\JobController;
use HotReloadStudios\Conductor\Http\Controllers\Api\JobStreamController;
use HotReloadStudios\Conductor\Http\Controllers\Api\MetricsController;
use HotReloadStudios\Conductor\Http\Controllers\Api\ScheduleController;
use HotReloadStudios\Conductor\Http\Controllers\Api\WorkerController;
use HotReloadStudios\Conductor\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('jobs', [JobController::class, 'index'])->name('conductor.api.jobs.index');
Route::get('jobs/{job}', [JobController::class, 'show'])->name('conductor.api.jobs.show');
Route::post('jobs/{job}/retry', [JobController::class, 'retry'])->name('conductor.api.jobs.retry');
Route::delete('jobs/{job}', [JobController::class, 'destroy'])->name('conductor.api.jobs.destroy');
Route::get('jobs/{job}/stream', JobStreamController::class)->name('conductor.api.jobs.stream');

Route::get('workflows', [WorkflowController::class, 'index'])->name('conductor.api.workflows.index');
Route::get('workflows/{workflow}', [WorkflowController::class, 'show'])->name('conductor.api.workflows.show');
Route::delete('workflows/{workflow}', [WorkflowController::class, 'destroy'])->name('conductor.api.workflows.destroy');

Route::get('events', [EventController::class, 'index'])->name('conductor.api.events.index');
Route::get('events/{event}', [EventController::class, 'show'])->name('conductor.api.events.show');

Route::get('schedules', [ScheduleController::class, 'index'])->name('conductor.api.schedules.index');
Route::post('schedules/{schedule}/toggle', [ScheduleController::class, 'toggle'])->name('conductor.api.schedules.toggle');

Route::get('metrics', [MetricsController::class, 'index'])->name('conductor.api.metrics.index');

Route::get('workers', [WorkerController::class, 'index'])->name('conductor.api.workers.index');
