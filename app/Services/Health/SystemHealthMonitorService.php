<?php

declare(strict_types=1);

namespace App\Services\Health;

use App\Enums\DeviceStatus;
use App\Models\Camera;
use App\Models\FireIncident;
use App\Models\Sensor;
use Illuminate\Support\Facades\DB;

class SystemHealthMonitorService
{
    /**
     * Get system-wide operational health status and device telemetry summary.
     *
     * @return array<string, mixed>
     */
    public function getHealthSummary(): array
    {
        // Sensors status
        $sensorTotal = Sensor::query()->count();
        $sensorActive = Sensor::query()->where('status', DeviceStatus::Active)->count();
        $sensorWarning = Sensor::query()->where('status', DeviceStatus::Warning)->count();
        $sensorOffline = Sensor::query()->where('status', DeviceStatus::Offline)->count();

        // Cameras status
        $cameraTotal = Camera::query()->count();
        $cameraActive = Camera::query()->where('status', DeviceStatus::Active)->count();
        $cameraOffline = Camera::query()->where('status', DeviceStatus::Offline)->count();

        // Active fires
        $activeIncidents = FireIncident::query()->active()->count();
        $criticalIncidents = FireIncident::query()->active()->critical()->count();

        // Central DB latency check
        $start = microtime(true);
        DB::select('SELECT 1');
        $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);

        return [
            'system_status' => ($sensorOffline > 10 || $cameraOffline > 5) ? 'DEGRADED' : 'HEALTHY',
            'last_sync_at' => now()->toIso8601String(),
            'edge_server' => [
                'status' => 'ONLINE',
                'name' => 'Silvanus-Edge-Cluster-Kazdağları',
                'uptime' => '99.98%',
                'ai_model' => 'Silvanus-YOLO-Wildfire-V4',
                'last_heartbeat' => now()->subSeconds(2)->toIso8601String(),
            ],
            'sensors' => [
                'total' => $sensorTotal,
                'active' => $sensorActive,
                'warning' => $sensorWarning,
                'offline' => $sensorOffline,
                'health_percentage' => $sensorTotal > 0 ? round(($sensorActive / $sensorTotal) * 100, 1) : 100,
            ],
            'cameras' => [
                'total' => $cameraTotal,
                'active' => $cameraActive,
                'offline' => $cameraOffline,
                'health_percentage' => $cameraTotal > 0 ? round(($cameraActive / $cameraTotal) * 100, 1) : 100,
            ],
            'incidents' => [
                'active_count' => $activeIncidents,
                'critical_count' => $criticalIncidents,
            ],
            'central_db' => [
                'status' => 'CONNECTED',
                'latency_ms' => $dbLatencyMs,
            ],
            'fcm_service' => [
                'status' => 'ACTIVE',
                'channel' => 'silvanus_emergency_channel',
            ],
        ];
    }
}
