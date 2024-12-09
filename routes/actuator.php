<?php
declare(strict_types=1);

use App\Http\Controllers\ActuatorController;
use Arquivei\LaravelPrometheusExporter\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/health/liveness', static fn() => response()->noContent())->name('liveness');
Route::get('/health/readiness', [ActuatorController::class, '__invoke'])->name('readiness');
Route::get('/prometheus', [MetricsController::class, 'getMetrics'])->name('metrics');
