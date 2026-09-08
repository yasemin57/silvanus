<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FireStation;
use App\Services\Dispatch\FireStationLocatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FireStationDispatchTest extends TestCase
{
    use RefreshDatabase;

    private FireStationLocatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FireStationLocatorService;
    }

    public function test_haversine_distance_calculation_accuracy(): void
    {
        // Kazdağları summit (39.7124, 26.8621) to Edremit (39.5938, 27.0244) ~ 19-20 km
        $dist = $this->service->calculateDistanceKm(39.7124, 26.8621, 39.5938, 27.0244);
        $this->assertGreaterThan(15.0, $dist);
        $this->assertLessThan(25.0, $dist);
    }

    public function test_nearest_fire_station_selection(): void
    {
        $closeStation = FireStation::query()->create([
            'code' => 'STN-CLOSE',
            'name' => 'Yakın İstasyon',
            'latitude' => 39.7100,
            'longitude' => 26.8600,
            'is_active' => true,
        ]);

        $farStation = FireStation::query()->create([
            'code' => 'STN-FAR',
            'name' => 'Uzak İstasyon',
            'latitude' => 41.0000,
            'longitude' => 29.0000,
            'is_active' => true,
        ]);

        $result = $this->service->findNearestStation(39.7120, 26.8610);

        $this->assertNotNull($result['nearest_station']);
        $this->assertEquals($closeStation->id, $result['nearest_station']->id);
        $this->assertLessThan(5.0, $result['distance_km']);
    }
}
