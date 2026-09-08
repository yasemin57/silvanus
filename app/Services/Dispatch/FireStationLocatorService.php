<?php

declare(strict_types=1);

namespace App\Services\Dispatch;

use App\Models\FireStation;

class FireStationLocatorService
{
    private const EARTH_RADIUS_KM = 6371.0;

    private const AVERAGE_FIRE_TRUCK_SPEED_KMH = 55.0; // Average forest road dispatch speed

    /**
     * Calculate geodesic distance between two points using Haversine formula.
     */
    public function calculateDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2.0) ** 2 + cos($lat1Rad) * cos($lat2Rad) * (sin($deltaLon / 2.0) ** 2);
        $c = 2.0 * atan2(sqrt($a), sqrt(1.0 - $a));

        return round(self::EARTH_RADIUS_KM * $c, 2);
    }

    /**
     * Calculate estimated response time in minutes.
     */
    public function estimateTravelTimeMinutes(float $distanceKm): int
    {
        // Add 2 minutes standard turn-out time + road travel time
        $travelHours = $distanceKm / self::AVERAGE_FIRE_TRUCK_SPEED_KMH;
        $travelMinutes = (int) ceil($travelHours * 60.0);

        return max(3, $travelMinutes + 2);
    }

    /**
     * Find nearest active fire station and rank all active stations by distance.
     *
     * @return array{
     *     nearest_station: ?FireStation,
     *     distance_km: ?float,
     *     estimated_minutes: ?int,
     *     ranked_stations: array<int, array{station: FireStation, distance_km: float, estimated_minutes: int}>
     * }
     */
    public function findNearestStation(float $targetLatitude, float $targetLongitude): array
    {
        $activeStations = FireStation::query()
            ->where('is_active', true)
            ->with(['firefighters' => fn ($query) => $query->where('status', 'AVAILABLE')])
            ->get();

        if ($activeStations->isEmpty()) {
            return [
                'nearest_station' => null,
                'distance_km' => null,
                'estimated_minutes' => null,
                'ranked_stations' => [],
            ];
        }

        $ranked = [];
        foreach ($activeStations as $station) {
            $dist = $this->calculateDistanceKm(
                $targetLatitude,
                $targetLongitude,
                (float) $station->latitude,
                (float) $station->longitude
            );

            $ranked[] = [
                'station' => $station,
                'distance_km' => $dist,
                'estimated_minutes' => $this->estimateTravelTimeMinutes($dist),
            ];
        }

        usort($ranked, fn (array $a, array $b) => $a['distance_km'] <=> $b['distance_km']);

        $nearest = $ranked[0];

        return [
            'nearest_station' => $nearest['station'],
            'distance_km' => $nearest['distance_km'],
            'estimated_minutes' => $nearest['estimated_minutes'],
            'ranked_stations' => $ranked,
        ];
    }
}
