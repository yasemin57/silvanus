<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CameraController;
use App\Http\Controllers\Api\EdgeTelemetryController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\OfflineSyncController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\SystemHealthController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/sensor-data', [SensorController::class, 'index']);
    Route::post('/sensor-data', [SensorController::class, 'store']);
    Route::get('/sensor-data/latest', [SensorController::class, 'latest']);
    // Public / Shared Live Feed Routes (Map & Health)
    Route::get('/map/elements', [MapController::class, 'elements']);
    Route::get('/system/health', [SystemHealthController::class, 'index']);
    Route::get('/incidents/active', [IncidentController::class, 'index']);
    Route::get('/incidents/history', [IncidentController::class, 'history']);
    Route::get('/incidents/{id}', [IncidentController::class, 'show']);
    Route::get('/sensors', [SensorController::class, 'index']);
    Route::get('/sensors/{id}', [SensorController::class, 'show']);
    Route::get('/cameras', [CameraController::class, 'index']);
    Route::get('/cameras/{id}', [CameraController::class, 'show']);
    Route::get('/stations', [StationController::class, 'index']);
    Route::get('/stations/{id}', [StationController::class, 'show']);

    // Edge Server Telemetry Ingestion & Simulator Triggers
    Route::post('/edge/telemetry-batch', [EdgeTelemetryController::class, 'ingestBatch']);
    Route::post('/edge/trigger-incident', [EdgeTelemetryController::class, 'triggerIncident']);

    // Protected Routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/incidents/{id}/transition', [IncidentController::class, 'transition']);
        Route::get('/offline/sync-bundle', [OfflineSyncController::class, 'syncBundle']);
    });
});
