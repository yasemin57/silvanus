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

class OfflineSyncController extends Controller
{
    /**
     * Download comprehensive offline cache bundle for mobile app field use in forest areas with spotty connectivity.
     */
    public function syncBundle(Request $request): JsonResponse
    {
        $user = $request->user()?->load(['firefighter.fireStation']);

        $activeIncidents = FireIncident::query()
            ->active()
            ->with([
                'fireStation',
                'primarySensor.latestReading',
                'primaryCamera',
                'timelines',
                'assignments.firefighter.user',
            ])
            ->get();

        $stations = FireStation::query()
            ->where('is_active', true)
            ->get();

        $sensors = Sensor::query()
            ->with('latestReading')
            ->get();

        $cameras = Camera::query()->get();

        $bundle = [
            'bundle_version' => '1.0.0',
            'generated_at' => now()->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->value,
                'firefighter' => $user->firefighter,
            ] : null,
            'incidents' => $activeIncidents,
            'stations' => $stations,
            'sensors' => $sensors,
            'cameras' => $cameras,
        ];

        $syncHash = md5(json_encode($bundle, JSON_THROW_ON_ERROR));

        return response()->json([
            'success' => true,
            'sync_hash' => $syncHash,
            'bundle' => $bundle,
        ]);
    }
}
