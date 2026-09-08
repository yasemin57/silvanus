<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DeviceStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Camera;
use App\Models\FireIncident;
use App\Models\FireStation;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@silvanus.gov.tr',
            'password' => 'secret123',
            'role' => UserRole::Firefighter,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@silvanus.gov.tr',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);
    }

    public function test_map_elements_returns_fires_sensors_cameras_stations(): void
    {
        $station = FireStation::query()->create([
            'code' => 'STN-01',
            'name' => 'İstasyon 1',
            'latitude' => 39.7,
            'longitude' => 26.8,
            'is_active' => true,
        ]);

        $sensor = Sensor::query()->create([
            'sensor_code' => 'SNS-01',
            'name' => 'Sensör 1',
            'latitude' => 39.71,
            'longitude' => 26.81,
            'status' => DeviceStatus::Active,
        ]);

        $camera = Camera::query()->create([
            'camera_code' => 'CAM-01',
            'name' => 'Kamera 1',
            'latitude' => 39.72,
            'longitude' => 26.82,
            'status' => DeviceStatus::Active,
        ]);

        $fire = FireIncident::query()->create([
            'incident_code' => 'INC-01',
            'title' => 'Yangın 1',
            'latitude' => 39.71,
            'longitude' => 26.81,
            'risk_score' => 95.0,
            'severity' => IncidentSeverity::Critical,
            'status' => IncidentStatus::Detected,
            'primary_sensor_id' => $sensor->id,
            'primary_camera_id' => $camera->id,
            'fire_station_id' => $station->id,
        ]);

        $response = $this->getJson('/api/v1/map/elements');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'counts' => ['active_fires', 'sensors', 'cameras', 'stations'],
                'data' => ['fires', 'sensors', 'cameras', 'stations'],
            ]);
    }

    public function test_offline_sync_bundle_returns_comprehensive_cache(): void
    {
        $user = User::factory()->create(['role' => UserRole::Firefighter]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/offline/sync-bundle');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'sync_hash',
                'bundle' => ['bundle_version', 'incidents', 'stations', 'sensors', 'cameras'],
            ]);
    }

    public function test_edge_batch_telemetry_ingestion(): void
    {
        $sensor = Sensor::query()->create([
            'sensor_code' => 'SNS-BATCH-01',
            'name' => 'Batch Test Sensör',
            'latitude' => 39.7,
            'longitude' => 26.8,
            'status' => DeviceStatus::Active,
        ]);

        $response = $this->postJson('/api/v1/edge/telemetry-batch', [
            'edge_node_id' => 'EDGE-NODE-01',
            'readings' => [
                [
                    'sensor_code' => 'SNS-BATCH-01',
                    'temperature' => 28.5,
                    'humidity' => 52.0,
                    'smoke_ppm' => 12.0,
                    'co_gas_ppm' => 5.0,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('processed_readings', 1);

        $this->assertDatabaseHas('sensor_readings', [
            'sensor_id' => $sensor->id,
            'temperature' => 28.5,
        ]);
    }
}
