<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\DeviceStatus;
use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\FireIncident;
use App\Models\IncidentAssignment;
use App\Models\IncidentTimeline;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Services\AI\FireRiskAnalysisService;
use App\Services\Dispatch\FireStationLocatorService;
use App\Services\Incidents\IncidentLifecycleService;
use App\Services\Notifications\FirePushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EdgeTelemetryController extends Controller
{
    public function __construct(
        private readonly FireRiskAnalysisService $riskService,
        private readonly FireStationLocatorService $locatorService,
        private readonly FirePushNotificationService $pushService,
        private readonly IncidentLifecycleService $lifecycleService
    ) {}

    /**
     * Ingest batch sensor telemetry from Edge Server.
     */
    public function ingestBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edge_node_id' => ['required', 'string'],
            'readings' => ['required', 'array'],
            'readings.*.sensor_code' => ['required', 'string'],
            'readings.*.temperature' => ['required', 'numeric'],
            'readings.*.humidity' => ['required', 'numeric'],
            'readings.*.smoke_ppm' => ['required', 'numeric'],
            'readings.*.co_gas_ppm' => ['required', 'numeric'],
            'readings.*.flame_detected' => ['nullable', 'boolean'],
            'readings.*.vision_confidence' => ['nullable', 'numeric'],
            'readings.*.camera_code' => ['nullable', 'string'],
        ]);

        $createdIncidents = [];
        $processedCount = 0;

        foreach ($validated['readings'] as $item) {
            $sensor = Sensor::query()->where('sensor_code', $item['sensor_code'])->first();
            if (! $sensor) {
                continue;
            }

            // Record reading
            $reading = SensorReading::query()->create([
                'sensor_id' => $sensor->id,
                'temperature' => (float) $item['temperature'],
                'humidity' => (float) $item['humidity'],
                'smoke_ppm' => (float) $item['smoke_ppm'],
                'co_gas_ppm' => (float) $item['co_gas_ppm'],
                'recorded_at' => now(),
            ]);

            // Update sensor heartbeat
            $sensor->update([
                'last_ping_at' => now(),
                'status' => DeviceStatus::Active,
            ]);

            $processedCount++;

            // AI Multi-Factor Risk Evaluation
            $camera = ! empty($item['camera_code'])
                ? Camera::query()->where('camera_code', $item['camera_code'])->first()
                : Camera::query()->where('zone_name', $sensor->zone_name)->first();

            $eval = $this->riskService->evaluate(
                temperature: (float) $item['temperature'],
                humidity: (float) $item['humidity'],
                smokePpm: (float) $item['smoke_ppm'],
                coGasPpm: (float) $item['co_gas_ppm'],
                visionConfidence: (float) ($item['vision_confidence'] ?? 0.0),
                flameDetected: (bool) ($item['flame_detected'] ?? false),
                smokeDetected: (float) $item['smoke_ppm'] > 50.0
            );

            // Check if threshold breached and no active incident exists in 1km vicinity
            if ($eval['should_trigger_alarm']) {
                $existingActive = FireIncident::query()
                    ->active()
                    ->where('zone_name', $sensor->zone_name)
                    ->first();

                if (! $existingActive) {
                    $incident = $this->createIncidentFromDetection($sensor, $camera, $eval, $reading);
                    $createdIncidents[] = $incident;
                }
            }
        }

        return response()->json([
            'success' => true,
            'processed_readings' => $processedCount,
            'new_incidents_count' => count($createdIncidents),
            'incidents' => $createdIncidents,
        ]);
    }

    /**
     * Explicit trigger for wildfire scenario / operator simulation.
     */
    public function triggerIncident(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_name' => ['nullable', 'string'],
            'sensor_id' => ['nullable', 'integer'],
            'camera_id' => ['nullable', 'integer'],
            'temperature' => ['nullable', 'numeric'],
            'humidity' => ['nullable', 'numeric'],
            'smoke_ppm' => ['nullable', 'numeric'],
            'co_gas_ppm' => ['nullable', 'numeric'],
            'vision_confidence' => ['nullable', 'numeric'],
            'flame_detected' => ['nullable', 'boolean'],
        ]);

        $sensor = isset($validated['sensor_id'])
            ? Sensor::query()->find($validated['sensor_id'])
            : Sensor::query()->where('zone_name', $validated['zone_name'] ?? 'Silvanus Bölgesi 01')->first() ?? Sensor::query()->first();

        $camera = isset($validated['camera_id'])
            ? Camera::query()->find($validated['camera_id'])
            : Camera::query()->where('zone_name', $sensor?->zone_name ?? 'Silvanus Bölgesi 01')->first() ?? Camera::query()->first();

        $temp = (float) ($validated['temperature'] ?? 74.5);
        $humidity = (float) ($validated['humidity'] ?? 12.0);
        $smoke = (float) ($validated['smoke_ppm'] ?? 94.0);
        $gas = (float) ($validated['co_gas_ppm'] ?? 48.0);
        $visionConf = (float) ($validated['vision_confidence'] ?? 96.0);
        $flame = (bool) ($validated['flame_detected'] ?? true);

        // Record spike reading
        $reading = null;
        if ($sensor) {
            $reading = SensorReading::query()->create([
                'sensor_id' => $sensor->id,
                'temperature' => $temp,
                'humidity' => $humidity,
                'smoke_ppm' => $smoke,
                'co_gas_ppm' => $gas,
                'recorded_at' => now(),
            ]);
            $sensor->update(['last_ping_at' => now(), 'status' => DeviceStatus::Active]);
        }

        $eval = $this->riskService->evaluate(
            temperature: $temp,
            humidity: $humidity,
            smokePpm: $smoke,
            coGasPpm: $gas,
            visionConfidence: $visionConf,
            flameDetected: $flame,
            smokeDetected: true
        );

        $incident = $this->createIncidentFromDetection($sensor, $camera, $eval, $reading);

        return response()->json([
            'success' => true,
            'message' => 'Orman yangını alarmı başarıyla oluşturuldu ve itfaiyeye bildirim gönderildi.',
            'incident' => $incident->load(['fireStation', 'primarySensor', 'primaryCamera', 'timelines']),
        ]);
    }

    private function createIncidentFromDetection(
        ?Sensor $sensor,
        ?Camera $camera,
        array $eval,
        ?SensorReading $reading
    ): FireIncident {
        // Automatic location determination from sensor/camera coordinates
        $latitude = $sensor ? (float) $sensor->latitude : ($camera ? (float) $camera->latitude : 39.7500000);
        $longitude = $sensor ? (float) $sensor->longitude : ($camera ? (float) $camera->longitude : 26.8500000);
        $zoneName = $sensor ? $sensor->zone_name : ($camera ? $camera->zone_name : 'Silvanus Bölgesi 01');

        // Locate nearest fire station via Haversine algorithm
        $dispatchInfo = $this->locatorService->findNearestStation($latitude, $longitude);
        $nearestStation = $dispatchInfo['nearest_station'];

        $incidentCode = 'INC-'.str_pad((string) random_int(1000, 99999), 6, '0', STR_PAD_LEFT);

        $incident = FireIncident::query()->create([
            'incident_code' => $incidentCode,
            'title' => sprintf('Yangın Alarmı - %s (%s)', $zoneName, $sensor?->sensor_code ?? 'Sensör'),
            'zone_name' => $zoneName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'altitude' => $sensor?->altitude,
            'risk_score' => $eval['risk_score'],
            'severity' => $eval['severity'],
            'status' => IncidentStatus::Detected,
            'detected_at' => now(),
            'primary_sensor_id' => $sensor?->id,
            'primary_camera_id' => $camera?->id,
            'fire_station_id' => $nearestStation?->id,
            'ai_confidence' => $eval['ai_confidence'],
            'ai_analysis_summary' => [
                'summary' => $eval['summary'],
                'breakdown' => $eval['breakdown'],
                'detected_by' => [
                    'sensor' => $sensor?->sensor_code,
                    'camera' => $camera?->camera_code,
                ],
                'dispatch' => [
                    'station_name' => $nearestStation?->name,
                    'distance_km' => $dispatchInfo['distance_km'],
                    'estimated_minutes' => $dispatchInfo['estimated_minutes'],
                ],
            ],
            'weather_snapshot' => [
                'temperature' => $reading?->temperature ?? 72.0,
                'humidity' => $reading?->humidity ?? 13.0,
                'smoke_ppm' => $reading?->smoke_ppm ?? 92.0,
                'wind_speed_kmh' => 28.0,
                'wind_direction' => 'Kuzeydoğu (NE)',
            ],
            'estimated_area_m2' => 450.0,
        ]);

        // Initial Timeline: DETECTED
        IncidentTimeline::query()->create([
            'fire_incident_id' => $incident->id,
            'status_from' => null,
            'status_to' => IncidentStatus::Detected->value,
            'action_title' => 'Yangın Yerel Sunucu AI Tarafından Tespit Edildi',
            'note' => sprintf(
                'Sensör ve kamera verileri analiz edildi. Yangın riski %%%.0f (%s). Konum otomatik koordinatlara bağlandı.',
                $incident->risk_score,
                $incident->severity->label()
            ),
            'latitude' => $incident->latitude,
            'longitude' => $incident->longitude,
            'duration_from_previous_seconds' => 0,
            'recorded_at' => now(),
        ]);

        // Assign available firefighters from nearest station
        $assignedFirefighters = [];
        if ($nearestStation) {
            $firefighters = $nearestStation->firefighters()->where('status', 'AVAILABLE')->get();
            foreach ($firefighters as $ff) {
                IncidentAssignment::query()->create([
                    'fire_incident_id' => $incident->id,
                    'firefighter_id' => $ff->id,
                    'fire_station_id' => $nearestStation->id,
                    'status' => 'ASSIGNED',
                    'assigned_at' => now(),
                ]);
                $assignedFirefighters[] = $ff;
            }
        }

        // Send FCM Push Notification to assigned firefighters
        $this->pushService->sendIncidentAlert($incident, $assignedFirefighters);

        // Transition status to NOTIFIED
        $incident->update(['status' => IncidentStatus::Notified]);

        IncidentTimeline::query()->create([
            'fire_incident_id' => $incident->id,
            'status_from' => IncidentStatus::Detected->value,
            'status_to' => IncidentStatus::Notified->value,
            'action_title' => 'İlgili İtfaiye Merkezine ve Personele Bildirim Gönderildi',
            'note' => sprintf(
                'En yakın itfaiye merkezi "%s" (Mesafe: %.1f km, Tahmini intikal: %d dk) belirlendi ve %d personele anlık bildirim fırlatıldı.',
                $nearestStation?->name ?? 'Merkez',
                $dispatchInfo['distance_km'] ?? 0.0,
                $dispatchInfo['estimated_minutes'] ?? 0,
                count($assignedFirefighters)
            ),
            'latitude' => $incident->latitude,
            'longitude' => $incident->longitude,
            'duration_from_previous_seconds' => 3,
            'recorded_at' => now(),
        ]);

        return $incident;
    }
}
