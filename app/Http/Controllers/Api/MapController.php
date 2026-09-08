<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\FireIncident;
use App\Models\FireStation;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function elements(Request $request): JsonResponse
    {
        // 1. Active Fire Incidents
        $incidents = FireIncident::query()
            ->active()
            ->with(['fireStation', 'primarySensor.latestReading', 'primaryCamera'])
            ->get()
            ->map(fn (FireIncident $fire) => [
                'type' => 'FIRE',
                'id' => $fire->id,
                'code' => $fire->incident_code,
                'title' => $fire->title,
                'zone_name' => $fire->zone_name,
                'latitude' => $fire->latitude,
                'longitude' => $fire->longitude,
                'risk_score' => $fire->risk_score,
                'severity' => $fire->severity->value,
                'severity_label' => $fire->severity->label(),
                'status' => $fire->status->value,
                'status_label' => $fire->status->label(),
                'ai_confidence' => $fire->ai_confidence,
                'detected_at' => $fire->detected_at?->toIso8601String(),
                'camera_stream_url' => $fire->primaryCamera?->stream_url,
                'station_name' => $fire->fireStation?->name,
                'latest_readings' => $fire->primarySensor?->latestReading ? [
                    'temperature' => $fire->primarySensor->latestReading->temperature,
                    'humidity' => $fire->primarySensor->latestReading->humidity,
                    'smoke_ppm' => $fire->primarySensor->latestReading->smoke_ppm,
                    'co_gas_ppm' => $fire->primarySensor->latestReading->co_gas_ppm,
                ] : null,
            ]);

        // 2. Sensors
        $sensors = Sensor::query()
            ->with('latestReading')
            ->get()
            ->map(fn (Sensor $sensor) => [
                'type' => 'SENSOR',
                'id' => $sensor->id,
                'code' => $sensor->sensor_code,
                'name' => $sensor->name,
                'zone_name' => $sensor->zone_name,
                'latitude' => $sensor->latitude,
                'longitude' => $sensor->longitude,
                'status' => $sensor->status->value,
                'battery_level' => $sensor->battery_level,
                'last_ping_at' => $sensor->last_ping_at?->toIso8601String(),
                'telemetry' => $sensor->latestReading ? [
                    'temperature' => $sensor->latestReading->temperature,
                    'humidity' => $sensor->latestReading->humidity,
                    'smoke_ppm' => $sensor->latestReading->smoke_ppm,
                    'co_gas_ppm' => $sensor->latestReading->co_gas_ppm,
                    'recorded_at' => $sensor->latestReading->recorded_at?->toIso8601String(),
                ] : null,
            ]);

        // 3. Cameras
        $cameras = Camera::query()
            ->get()
            ->map(fn (Camera $cam) => [
                'type' => 'CAMERA',
                'id' => $cam->id,
                'code' => $cam->camera_code,
                'name' => $cam->name,
                'zone_name' => $cam->zone_name,
                'latitude' => $cam->latitude,
                'longitude' => $cam->longitude,
                'status' => $cam->status->value,
                'fov_degrees' => $cam->fov_degrees,
                'orientation_degrees' => $cam->orientation_degrees,
                'stream_url' => $cam->stream_url,
                'snapshot_url' => $cam->snapshot_url,
                'has_thermal' => $cam->has_thermal,
            ]);

        // 4. Fire Stations
        $stations = FireStation::query()
            ->where('is_active', true)
            ->withCount(['firefighters as available_firefighters_count' => fn ($q) => $q->where('status', 'AVAILABLE')])
            ->get()
            ->map(fn (FireStation $station) => [
                'type' => 'FIRE_STATION',
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'phone' => $station->phone,
                'coverage_radius_km' => $station->coverage_radius_km,
                'active_units_count' => $station->active_units_count,
                'available_firefighters_count' => $station->available_firefighters_count,
            ]);

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'counts' => [
                'active_fires' => $incidents->count(),
                'sensors' => $sensors->count(),
                'cameras' => $cameras->count(),
                'stations' => $stations->count(),
            ],
            'data' => [
                'fires' => $incidents,
                'sensors' => $sensors,
                'cameras' => $cameras,
                'stations' => $stations,
            ],
        ]);
    }
}
